<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\FileManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse, FileManager;
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        if (!auth()->user()->admin){
            return $this->errorResponse([
                'errorCode' => 'UNAUTHORIZED',
                'message'   => 'Unauthorized user'
            ], 401);
        }

        $users = User::all();
        return $this->showAll($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'lastname'      => 'required|string|max:255',
            'firstname'     => 'required|string|max:255',
            'middlename'    => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email',
            'phone'         => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'password'      => 'required|string|max:255|confirmed',
            'image'         => 'nullable|image|max:3000'
        ]);

        $image_name = null;
        // Check if an image is uploaded
        if ($request->hasFile('image')){
            $image_name = $this->saveUserImage($request->image, 'images');
        }

        $user = User::create([
            'lastname'      => $request->lastname,
            'firstname'     => $request->firstname,
            'middlename'    => $request->middlename,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'image'         => $image_name,
            'password'      => $request->password
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        return $this->successResponse([
            'errorCode' => 'SUCCESS',
            'token'     => $token,
            'data'      => $user
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        if ($this->isOwnerOrAdmin($user)){
            return $this->showOne($user, 200);
         }

        return $this->errorResponse([
            'errorCode' => 'UNAUTHORIZED',
            'message'   => 'Unauthorized user'
        ], 401);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (!$this->isOwnerOrAdmin($user)){
            return $this->errorResponse([
                'errorCode' => 'UNAUTHORIZED',
                'message'   => 'Unauthorized user'
            ], 401);
        }


        $request->validate([
            'lastname'      => 'string|max:255',
            'firstname'     => 'string|max:255',
            'middlename'    => 'string|max:255',
            'email'         => 'string|email|max:255|unique:users,email',
            'phone'         => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'image'         => 'nullable|image|max:3000',
            'admin'         => 'boolean',
            'active'        => 'boolean'
        ]);


        $user->fill($request->only([
            'lastname',
            'firstname',
            'middlename',
            'email',
            'phone',
            'admin',
            'active'
        ]));

        if ($request->has('admin')){
            // Check if the current user is an admin
            if (auth()->user()->admin){
                $user->admin = $request->admin;
            }else{
                // Only an admin can set an admin status
                return $this->errorResponse([
                    'errorCode' => 'UNAUTHORIZED',
                    'message'   => 'Only an admin can set an admin status'
                ], 401);
            }
        }

        if ($request->has('active')){
            // Check if the current user is an admin
            if (auth()->user()->admin){
                $user->active = $request->active;
            }else{
                // Only an admin can set an admin status
                return $this->errorResponse([
                    'errorCode' => 'UNAUTHORIZED',
                    'message'   => 'Only an admin can set user active status'
                ], 401);
            }
        }

        if ($request->hasFile('image')){
            $this->deleteUserImage($user->image);

            $request->image = $this->saveUserImage($request->image, 'images');
        }

        $user->save();

        return $this->successResponse([
            'errorCode' => 'SUCCESS',
             'data'     => $user
        ],201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        if (!$this->isOwnerOrAdmin($user)){
            return $this->errorResponse([
                'errorCode' => 'UNAUTHORIZED',
                'message'   => 'Unauthorized user'
            ], 401);
        }

        if ($user->image){
            $this->deleteUserImage($user->image);
        }

        $user->delete();

        return $this->successResponse([
            'errorCode' => 'SUCCESS',
            'message'   => 'User deleted successfully'
        ], 202);
    }

    public function isOwnerOrAdmin(User $user): bool
    {
        if (auth()->user()->admin || $user->id == auth()->user()->id){
            return true;
        }
        return false;
    }

}
