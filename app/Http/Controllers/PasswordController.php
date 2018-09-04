<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Services\PasswordService;

class PasswordController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request, PasswordService $passwordService)
    {
        $user = Auth::user();
        $this->validatePassword($request, $user->id);
        $password = $passwordService->create($user->id, $request->all());

        if ($password) {
            return response()->json($password);
        }

        throw new \Exception('Problem creating password');
    }

    public function update(Request $request, PasswordService $passwordService, $id)
    {
        $user = Auth::user();
        $passwordService->get($id, $user->id);
        $this->validatePasswordUpdate($request, $user->id, $id);

        $masterPassword = $request->input('master_password');
        $password = $passwordService->update($id, $masterPassword, $request->all());

        if ($password) {
            return response()->json($password);
        }

        throw new \Exception('Problem updating password');
    }

    public function delete(Request $request, PasswordService $passwordService, $id)
    {
        $user = Auth::user();
        $passwordService->get($id, $user->id);

        $this->validateMasterPassword($request);

        $masterPassword = $request->input('master_password');
        $passwordDeleted = $passwordService->delete($id, $masterPassword);

        if ($passwordDeleted) {
            return response()->json();
        }

        throw new \Exception('Problem deleting password');
    }

    public function all(Request $request, PasswordService $passwordService)
    {
        $user = Auth::user();
        return response()->json($passwordService->all($user->id));
    }

    public function unlock(Request $request, PasswordService $passwordService, $id)
    {
        $user = Auth::user();
        
        $passwordService->get($id, $user->id);

        $this->validateUnlock($request);
        $masterPassword = $request->input('master_password');

        $password = $passwordService->unlock($id, $user->id, $masterPassword);
        if ($password) {
            return response()->json($password);
        }

        throw new \Exception('Problem unlocking password');
    }

    private function validatePassword($request, $userId)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'account_name' => 'required|string|unique:password,account_name,NULL,id,name,' . $request->input('name').',user_id,'.$userId, 
            'password' => 'required|string',
        ],[],
        [
            'name' => trans('messages.name'),
            'account_name' => trans('messages.account_name'),
            'password' => trans('messages.password'),
        ]);
    }

    private function validatePasswordUpdate($request, $userId, $id)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'account_name' => 'required|string|unique:password,account_name,'.$id.',id,name,' . $request->input('name').',user_id,'.$userId, 
            'password' => 'required|string',
            'master_password' => 'required|string',
        ],[],
        [
            'name' => trans('messages.name'),
            'account_name' => trans('messages.account_name'),
            'password' => trans('messages.password'),
            'master_password' => trans('messages.master_password'),
        ]);
    }

    private function validateMasterPassword($request)
    {
        $this->validate($request, [
            'master_password' => 'required|string',
        ],[],
        [
            'master_password' => trans('messages.master_password'),
        ]);
    }

    private function validateUnlock($request)
    {
        $this->validate($request, [
            'master_password' => 'required|string',
        ],[],
        [
            'master_password' => trans('messages.master_password'),
        ]);
    }
}
