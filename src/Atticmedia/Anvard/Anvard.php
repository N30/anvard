<?php namespace Atticmedia\Anvard;

use Hybrid_Auth;
use Hybrid_Provider_Adapter;
use Hybrid_User_Profile;
use Illuminate\Log\Writer;
use Atticmedia\Anvard\Models\User as User;

class Anvard {

    /**
     * The configuration for Anvard
     * 
     * @var array
     */
    protected $config;

    /**
     * The service used to login
     * 
     * @var Hybrid_Provider_Adapter $adapter
     */
    protected $adapter;


    /**
     * The profile of the current user from
     * the provider, once logged in
     * 
     * @var Hybrid_User_Profile
     */
    protected $adapter_profile;

    /**
     * The name of the current provider,
     * e.g. Facebook, LinkedIn, etc
     * 
     * @var string
     */
    protected $provider;

    /**
     * The logger
     * 
     * @var Writer
     */
    protected $logger;

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Get a social profile for a user, optionally specifying
     * which social network to get, and which user to query
     */
    public function getProfile($network = NULL, $user = NULL) {
        if ( $user === NULL ) {
            $user = User::find(Auth::user()->id);
            if (!$user) {
                return NULL;
            }
        }
        if ($network === NULL) {
            $profile = $user->profiles()->first();
        } else {
            $profile = $user->profiles()->where('network', $network)->first();
        }
        return $profile;
    }

    public function getProviders() {
        $haconfig = $this->config['hybridauth'];
        $providers = array();
        foreach ($haconfig['providers'] as $provider => $config) {
            if ( $config['enabled'] ) {
                $providers[] = $provider;
            }
        }
        return $providers;
    }


    /**
     * @return String
     */
    public function getCurrentProvider() {
        return $this->provider;
    }
    public function setCurrentProvider(String $provider) {
        $this->provider = $provider;
    }

    /**
     * @return Hybrid_Provider_Adapter
     */
    public function getAdapter() {
        return $this->adapter;
    }
    public function setAdapter(Hybrid_Provider_Adapter $adapter) {
        $this->adapter = $adapter;
    }


    /**
     * @return Writer
     */
    public function getLogger() {
        return $this->logger;
    }
    public function setLogger(Writer $logger) {
        $this->logger = $logger;
    }

    /**
     * Attempt a login with a given provider
     */
    public function attemptAuthentication($provider, Hybrid_Auth $hybridauth) {
        try {
            $this->provider = $provider;
            $adapter = $hybridauth->authenticate($provider);
            $this->setAdapter($adapter);
            $this->setAdapterProfile($adapter->getUserProfile());
            $profile = $this->findProfile();
            return $profile;
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function setAdapterProfile(Hybrid_User_Profile $profile) {
        $this->adapter_profile = $profile;
    }

    /**
     * @return Hybrid_User_Profile
     */
    public function getAdapterProfile() {
        return $this->adapter_profile;
    }

    public function getProfile($adapterProfile)
    {
        $profileModel = $this->config['db']['profilemodel'];
        // See if the current social profile already exists in the profiles table
        $profile = $profileModel::with(array('user'))
            ->whereProvider($this->provider)
            ->whereIdentifier($adapterProfile->identifier)
            ->first();
        return $profile;
    }

    protected function getUser($adapterProfile)
    {
        if ($profile = $this->getProfile($adapterProfile)) {
            // ok, we found an existing user
            $user = $profile->user;
            $this->logger->debug('Anvard: found a profile, id='.$profile->id);
        } else {
            $this->logger->debug('Anvard: could not find profile, looking for email');
            // ok it's a new profile ... can we find the user by email?
            $userModel = $this->config['db']['usermodel'];
            $user = $userModel::newQuery()
                ->whereEmail($adapterProfile->email)
                ->first();
        }
        return $user;
    }

    protected function findProfile()
    {
        $adapterProfile = $this->getAdapterProfile();

        // Everything is a-ok already so just return the user profile
        if($user = $this->getUser($adapterProfile)) return $user->profile;
        $userModel = $this->config['db']['usermodel'];
        
        $this->logger->debug('Anvard: did not find user, creating');
        $user = new $UserModel();
            // map in anything from the profile that we want in the User
            $map = $this->config['db']['profiletousermap'];
            foreach ($map as $apkey => $ukey) {
                $user->$ukey = $adapter_profile->$apkey;
            }
            $values = $this->config['db']['uservalues'];
            foreach ( $values as $key=>$value ) {
                if (is_callable($value)) {
                    $user->$key = $value($user, $adapter_profile);
                } else {
                    $user->$key = $value;
                }
            }
            // @todo this is all very custom ... how to fix?
            $user->role_id = 3;
            $user->username = $adapter_profile->email;
            $user->email = $adapter_profile->email;
            $user->password = uniqid();
            $user->password_confirmation = $user->password;
            $rules = $this->config['db']['userrules'];
            $result = $user->save($rules);
            if ( !$result ) {
                $this->logger->error('Anvard: FAILED TO SAVE USER');
                return NULL;
            }
        }
        if (!$profile) {
            // If we didn't find the profile, we need to create a new one
            $profile = $this->createProfileFromAdapterProfile($adapter_profile, $user);
        } else {
            // If we did find a profile, make sure we update any changes to the source
            $profile = $this->applyAdapterProfileToExistingProfile($adapter_profile, $profile);
        }
        $result = $profile->save();
        if (!$result) {
            $this->logger->error('Anvard: FAILED TO SAVE PROFILE');
            return NULL;
        }
        $this->logger->info('Anvard: succesful login!');
        return $profile;

    }

    protected function createProfileFromAdapterProfile($adapter_profile, $user) {
        $ProfileModel = $this->config['db']['profilemodel'];
        $attributes['provider'] = $this->provider;
        // @todo use config value for foreign key name
        $attributes['user_id'] = $user->id;
        $profile = new $ProfileModel($attributes);
        $profile = $this->applyAdapterProfileToExistingProfile($adapter_profile, $profile);
        return $profile;
    }

    protected function applyAdapterProfileToExistingProfile($adapter_profile, $profile) {
        $attributes = get_object_vars($adapter_profile);
        foreach ($attributes as $k=>$v) {
            $profile->$k = $v;
        }
        return $profile;
    }


}
