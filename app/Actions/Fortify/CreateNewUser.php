<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $nameAndUsername = explode(' ',$input['name']);
        
        if (count($nameAndUsername) === 2){
            $input['surname'] = $nameAndUsername[count($nameAndUsername) - 1];
        }
        elseif (count($nameAndUsername) > 2){
            $input['surname'] = $nameAndUsername[count($nameAndUsername) - 2] . ' ' .  $nameAndUsername[count($nameAndUsername) - 1];
        }   


        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'g-recaptcha-response' => ['required', 'captcha'],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'surname' => $input['surname'],
            'nickname' => $input['nickname'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role_id' => 4,
            'g-recaptcha-response' => $input['g-recaptcha-response']
        ]);
    }
}
