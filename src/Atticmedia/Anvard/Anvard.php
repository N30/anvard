<?php namespace Atticmedia\Anvard;

use Hybrid_Auth;
use Hybrid_Provider_Adapter;
use Hybrid_User_Profile;
use Illuminate\Log\Writer;
use Atticmedia\Anvard\Models\User as User;
use Config;

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
    protected $adapterProfile;

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
        $this->adapterProfile = $profile;
    }

    /**
     * @return Hybrid_User_Profile
     */
    public function getAdapterProfile() {
        return $this->adapterProfile;
    }

    public function getProfile($adapterProfile)
    {
        $profileModel = $this->config['db']['profilemodel'];
        // See if the current social profile already exists in the profiles table
        $profile = $profileModel::with(array('user'))
            ->whereProvider($this->provider)
            ->whereIdentifier($adapterProfile->identifier)
            ->first();
        // Profile doesn't exist in DB yet so create it and attach it to $user
        if(!$profile) $profile = $this->createProfileFromAdapterProfile($adapterProfile);
        return $profile;
    }

    protected function getUser($adapterProfile)
    {
        $userModel = $this->config['db']['usermodel'];
        $user = $userModel::whereEmail($adapterProfile->email)
            ->first();
        if(!$user) $user = $this->createUserFromProfile($adapterProfile);
        $result = $user->save();
        if ( !$result ) {
            $this->logger->error('Anvard: FAILED TO SAVE USER');
            return NULL;
        }
        return $user;
    }

	protected function createUserFromProfile($adapterProfile)
	{
        $this->logger->debug('Anvard: did not find user, creating');
        // Create a new user to attach the profile to
        $userModel = $this->config['db']['usermodel'];
        $user = new $userModel();
        // map in anything from the profile that we want in the User
        // Don't really need this right now...
        $map = $this->config['db']['profiletousermap'];
        foreach ($map as $apkey => $ukey) {
            $user->$ukey = $adapterProfile->$apkey;
        }
        /*$values = $this->config['db']['uservalues'];
        foreach ( $values as $key=>$value ) {
            if (is_callable($value)) {
                $user->$key = $value($user, $adapterProfile);
            } else {
                $user->$key = $value;
            }
        }*/
		return $user;
	}

    protected function findProfile()
    {
        $adapterProfile = $this->getAdapterProfile();
        $profile = $this->getProfile($adapterProfile);
        if(!($user = $profile->user)) $user = $this->getUser($adapterProfile);
        $profile = $this->attachProfileToUser($profile, $user);
        $this->logger->info('Anvard: succesful login!');
		// Return the profile
        return $profile;
    }

    protected function createProfileFromAdapterProfile($adapterProfile) {
        $profileModel = $this->config['db']['profilemodel'];
        $profile = new $profileModel();
        $profile->provider = $this->provider;
        $attributes = get_object_vars($adapterProfile);
        foreach ($attributes as $k=>$v) {
            $profile->$k = $v;
        }
        return $profile;
    }
    
    protected function attachProfileToUser($profile,$user)
    {
        $userFk = Config::get('anvard::db.profilestableforeignkey','user_id');
        if($profile->$userFk == $user->id) return $profile;
        $profile->$userFk = $user->id;
        $result = $profile->save();
        if ( !$result ) {
            $this->logger->error('Anvard: FAILED TO ATTACH PROFILE TO USER');
            return NULL;
        }
        return $profile;
    }

}
