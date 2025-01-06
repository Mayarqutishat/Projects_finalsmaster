<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the form data
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]/'], // الاسم يجب أن يبدأ بحرف
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255','regex:/^[A-Za-z]/', 'unique:' . User::class],
            'password' => [
                'required', 
                'confirmed', 
                Rules\Password::defaults(),
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/' // كلمة السر يجب أن تحتوي على حرف كبير، رقم، ورمز
            ],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\d{10}$/'], // رقم الهاتف يجب أن يكون 10 أرقام
            'gender' => ['required', 'in:male,female'],
            'age' => ['required', 'integer', 'min:18'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'user_role' => ['required', 'in:customer,admin'],
        ], [
            'name.regex' => 'The name must start with a letter.',
            'password.regex' => 'The password must contain at least one uppercase letter, one number, and one special character.',
            'phone.regex' => 'The phone number must be exactly 10 digits.',
        ]);

        // Store the profile image
        $imagePath = $request->file('image')->store('images', 'public');

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'age' => $request->age,
            'image' => $imagePath,
            'user_role' => $request->user_role,
        ]);

        // Fire the Registered event
        event(new Registered($user));

        // Log the user in
        Auth::login($user);

        // Redirect the user
        // return redirect(RouteServiceProvider::inde);
        return redirect()->route('index');
    }
}