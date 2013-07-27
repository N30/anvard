<?php namespace Atticmedia\Anvard\Controllers;

use Config, App, View, Session, Redirect, Log, Auth;
use Illuminate\Routing\Controllers\Controller;
use Hybrid_Endpoint;

class AnvardController extends Controller
{
    public function index()
    {
        $view = Config::get('anvard::views.index');
        $anvard = App::make('anvard');
        $providers = $anvard->getProviders();

        return View::make($view, compact('providers'));
    }

    public function login($provider)
    {
        Log::debug('Anvard: attempting login');
        $hybridAuth = App::make('hybridauth');
        $profile = App::make('anvard')->attemptAuthentication($provider, $hybridAuth);
        Log::debug('Anvard: login attempt complete');
        if ($profile) {
            Log::debug('Anvard: login success');
            Auth::loginUsingId($profile->user->id);
        } else {
            Log::debug('Anvard: login failure');
            Session::flash('anvard', 'Failed to log in!');
        }
        //Hybrid_Endpoint::process();
        return Redirect::to('/');
    }

    public function logout($provider)
    {
        $hybridAuth = App::make('hybridauth');
        $anvard = App::make('anvard');
        $profile = $anvard->attemptAuthentication($provider, $hybridAuth);
        $adapter = $anvard->getAdapter();
        $adapter->logout();
        Auth::logout();

        return Redirect::back();
    }

    public function endpoint()
    {
        Hybrid_Endpoint::process();

        return Redirect::to('/');
    }
}
