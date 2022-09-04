<?php

namespace Engelsystem\Controllers\Admin;

use Engelsystem\Controllers\BaseController;
use Engelsystem\Controllers\CleanupModel;
use Engelsystem\Controllers\HasUserNotifications;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Models\Faq;
use Engelsystem\Models\User\User;
use Psr\Log\LoggerInterface;

class ImportUsersCotroller extends BaseController
{
    /** @var LoggerInterface */
    protected $log;

    /** @var Faq */
    protected $faq;

    /** @var User */
    protected $user;

    /** @var Redirector */
    protected $redirect;

    /** @var Response */
    protected $response;

    /** @var array */
    protected $permissions = [
        'admin_user',
    ];

    /**
     * @param LoggerInterface $log
     * @param Faq             $faq
     * @param Redirector      $redirector
     * @param Response        $response
     * @param User            $user
     */
    public function __construct(
        LoggerInterface $log,
        Faq $faq,
        Redirector $redirector,
        Response $response,
        User $user,
    ) {
        $this->log = $log;
        $this->faq = $faq;
        $this->redirect = $redirector;
        $this->response = $response;
        $this->user = $user;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->response->withView(
            'admin/user/import.twig',
            []
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function process(Request $request): Response
    {
        $files = $request->getUploadedFiles();
        if (count($files) != 1) {
            return $this->response->withView(
                'admin/user/import.twig',
                []
            );
        }
        $lines = explode("\n", $files[0]->getStream()->getContents());
        array_shift($lines); // Drop headers
        foreach ($lines as $l) {
            if ($l == "") {
                continue;
            }

            // username,password,email,mobile,first,last
            $u = str_getcsv($l);
            $usr = new User();
            $usr->name = $u[0];
            $usr->password = password_hash($u[1], PASSWORD_DEFAULT);
            $usr->email = $u[2];
            $usr->api_key = md5($u[0] . rand() . time() . rand());
            $usr->save();

            $usr->contact->email = $u[2];
            $usr->contact->mobile = $u[3];

            $usr->personalData->first_name = $u[4];
            $usr->personalData->last_name = $u[5];
            $usr->push();
        }

        return $this->response->withView(
            'admin/user/import.twig',
            []
        );
    }
}
