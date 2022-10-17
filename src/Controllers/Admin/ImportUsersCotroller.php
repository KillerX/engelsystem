<?php

namespace Engelsystem\Controllers\Admin;

use Engelsystem\Controllers\BaseController;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Redirector;
use Engelsystem\Mail\EngelsystemMailer;
use Illuminate\Database\Capsule\Manager as DB;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Models\Faq;
use Engelsystem\Models\User\User;
use Psr\Log\LoggerInterface;
use Engelsystem\Models\AngelType;

class ImportUsersCotroller extends BaseController
{
    /** @var Authenticator */
    protected $auth;

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

    /** @var AngelType */
    protected $angelType;

    /** @var EngelsystemMailer */
    protected $mail;

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
     * @param AngelType       $angelType
     * @param EngelsystemMailer $mail
     */
    public function __construct(
        LoggerInterface $log,
        Faq $faq,
        Redirector $redirector,
        Response $response,
        User $user,
        AngelType $angelType,
        EngelsystemMailer $mail,
        Authenticator $auth,
    ) {
        $this->log = $log;
        $this->faq = $faq;
        $this->redirect = $redirector;
        $this->response = $response;
        $this->user = $user;
        $this->angelType = $angelType;
        $this->mail = $mail;
        $this->auth = $auth;
    }

    public function generateRandomString($length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
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
            [
                'userCount' => '',
            ]
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
                [
                    'userCount' => '',
                ]
            );
        }

        $angelTypes = $this->angelType->get();
        $angelTypesMap = [];

        foreach ($angelTypes as $t) {
            $angelTypesMap[$t->name] = $t;
        }


        $lines = explode("\n", $files[0]->getStream()->getContents());

        $header = str_getcsv(array_shift($lines));

        $angelTypesInFile = (array_slice($header, 7));

        foreach ($angelTypesInFile as $i => $atif) {
            if (!array_key_exists($atif, $angelTypesMap)) {
                die("Unknown worker type: " . $atif);
            }

            $angelTypesMap[$i] = $angelTypesMap[$atif];
        }

        $userCount = 0;
        $newUserIDs = [];

        foreach ($lines as $l) {
            if ($l == "") {
                continue;
            }

            // Ansattnummer,Fornavn,Etternavn,Født,E-postadresse,Telefonnummer,Alder, <JOBS> ....
            // 0           ,   1   ,    2    , 3  ,       4     ,      5      , 6   ,      7    ,           8
            $u = str_getcsv($l);

            $username = $u[1] . " " . $u[2];
            $password = $this->generateRandomString(8);

            $usr = new User();
            $usr->name = $username;
            $usr->password = password_hash($password, PASSWORD_DEFAULT);
            $usr->email = $u[4];
            $usr->api_key = md5($u[0] . rand() . time() . rand());
            $usr->save();

            $usr->contact->email = $u[4];
            $usr->contact->mobile = $u[5];

            $usr->personalData->first_name = $u[1];
            $usr->personalData->last_name = $u[2];
            $usr->personalData->employee_number = $u[0];
            $usr->personalData->birthday = $u[3];
            $usr->personalData->shirt_size = 'XL';
            $usr->personalData->planned_arrival_date = '01-01-1970';
            $usr->personalData->planned_departure_date = '31-12-3000';

            //$bday = date_parse_from_format('j/n/Y', $u[3]);

            $usr->settings->email_human = true;
            $usr->settings->email_shiftinfo = true;
            $usr->settings->theme = 1;
            $usr->settings->language = 'en_US';


            $angelTypesToAssignIDs = [];

            $angelTypesToAssign = array_slice($u, 7);
            foreach ($angelTypesToAssign as $i => $toAssign) {
                if (strtolower($toAssign) != 'yes') {
                    continue;
                }

                $angelTypesToAssignIDs[] = $angelTypesMap[$i]->id;
            }

            $usr->angelTypes()->sync($angelTypesToAssignIDs);
            $usr->push();

            DB::table('UserGroups')->insert([
                'uid' => $usr->id,
                'group_id' => -20, // Worker
            ]);

            $userCount++;

            $newUserIDs[] = $usr->id;

            $this->mail->sendView(
                $usr->email,
                'BD-Service Account',
                'emails/new_account',
                [
                    'username' => $this->user->name,
                    'pass' => $password,
                ]
            );
        }

        // Accept all roles for all new users
        DB::update(DB::raw("UPDATE UserAngelTypes SET confirm_user_id = 1 WHERE user_id IN (".implode(',', $newUserIDs).");"));

        return $this->response->withView(
            'admin/user/import.twig',
            [
                'userCount' => $userCount,
            ]
        );
    }
}
