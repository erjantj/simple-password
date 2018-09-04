<?php
namespace App\Services;

use Illuminate\Support\Facades\Hash;

use App\Models\Password;
use App\Models\User;

class PasswordService
{
    public function get($id, $userId = null)
    {
        $query = Password::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->findOrFail($id);
    }
    
    public function create($userId, $data)
    {
        $password = new Password;
        $password->name = $data['name'];
        $password->user_id = $userId;
        $password->account_name = $data['account_name'];
        $password->password_encrypted = encrypt($data['password']);

        if ($password->save()) {
            return $password;
        }

        return null;
    }

    public function update($id, $masterPassword, $data)
    {
        $password = Password::with(['user'])->findOrFail($id);

        $this->validateMasterPassword($password->user, $masterPassword);

        $password->name = $data['name'];
        $password->account_name = $data['account_name'];
        $password->password_encrypted = encrypt($data['password']);

        if ($password->save()) {
            return $this->get($password->id);
        }

        return null;
    }

    public function delete($id, $masterPassword)
    {
        $password = Password::with(['user'])->findOrFail($id);

        $this->validateMasterPassword($password->user, $masterPassword);

        return $password->delete();
    }

    public function all($userId)
    {
        return Password::where('user_id', $userId)->get();
    }

    public function unlock($id, $userId, $masterPassword)
    {
        $password = Password::findOrFail($id);
        $user = User::findOrFail($userId);

        $this->validateMasterPassword($user, $masterPassword);

        return [
            'password' => decrypt($password->password_encrypted)
        ];
    }

    private function validateMasterPassword($user, $masterPassword)
    {
        if (Hash::check($masterPassword, $user->master_password)) {
            return true;
        }

        abort(422, trans('validation.invalid', [
            'attribute' => trans('messages.master_password')
        ]));
    }
}