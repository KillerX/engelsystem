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

class CreateUserController extends BaseController
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
     * @param Faq $faq
     * @param Redirector $redirector
     * @param Response $response
     * @param User $user
     * @param AngelType $angelType
     * @param EngelsystemMailer $mail
     */
    public function __construct(
        LoggerInterface   $log,
        Faq               $faq,
        Redirector        $redirector,
        Response          $response,
        User              $user,
        AngelType         $angelType,
        EngelsystemMailer $mail,
        Authenticator     $auth,
    )
    {
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
            'admin/user/create.twig',
            [
                'created' => false,
                'angelTypes' => $this->angelType->get(),
            ]
        );
    }

    private function createSingleUser(Request $request): Response
    {
        $firstName = $request->request->get('first_name');
        $lastName = $request->request->get('last_name');
        $email = $request->request->get('email');
        $employeeNumber = $request->request->get('employee_number');
        $birthday = $request->request->get('date_of_birth');
        $phone = $request->request->get('phone');
        $password = $this->generateRandomString(8);

        $usr = new User();
        $usr->name = $firstName . " " . $lastName;
        $usr->password = password_hash($password, PASSWORD_DEFAULT);
        $usr->email = $email;
        $usr->api_key = md5($firstName . $lastName . rand() . time() . rand());
        $usr->save();

        $usr->contact->email = $email;
        $usr->contact->mobile = $phone;

        $usr->personalData->first_name = $firstName;
        $usr->personalData->last_name = $lastName;
        $usr->personalData->employee_number = $employeeNumber;
        $usr->personalData->birthday = $birthday;
        $usr->personalData->shirt_size = 'XL';
        $usr->personalData->planned_arrival_date = '01-01-1970';
        $usr->personalData->planned_departure_date = '31-12-3000';

        $usr->settings->email_human = true;
        $usr->settings->email_shiftinfo = true;
        $usr->settings->theme = 1;
        $usr->settings->language = 'en_US';

        $angelTypesToAssignIDs = [];
        foreach ($this->angelType->get() as $angelType) {
            if ($request->request->get($angelType->id) == '1') {
                $angelTypesToAssignIDs[] = $angelType->id;
            }

        }

        $usr->angelTypes()->sync($angelTypesToAssignIDs);
        $usr->push();

        DB::table('UserGroups')->insert([
            'uid' => $usr->id,
            'group_id' => -20, // Worker
        ]);

        DB::table('users_state')->insert([
            'user_id' => $usr->id,
            'arrived' => 1,
            'arrival_date' => new \DateTimeImmutable(),
            'active' => 1,
        ]);

        $this->mail->sendView(
            $usr->email,
            'BD-Service Account',
            'emails/new_account',
            [
                'username' => $this->user->name,
                'pass' => $password,
            ]
        );

        // Accept all roles for all new users
        DB::update(DB::raw("UPDATE UserAngelTypes SET confirm_user_id = 1 WHERE user_id = ".$usr->id.";"));

        return $this->response->withView(
            'admin/user/create.twig',
            [
                'created' => true,
                'angelTypes' => $this->angelType->get(),
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

        if ($request->request->get('formimport') == '1') {
            return $this->createSingleUser($request);
        }


        return $this->response->withView(
            'admin/user/create.twig',
            [
                'created' => false,
                'angelTypes' => $this->angelType->get(),
            ]
        );
    }
}
