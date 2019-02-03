<?php

namespace Geneanet;

/* geany_encoding=ISO-8859-15 */



class GeneanetServer
{

    protected $login;
    protected $passwd;

    protected $curl; # handle IO with curl library


    public function __construct()
    {
        $this->curl = new CURL();
    }

    public function login($user, $passwd)
    {

        $args = array(
            "_username"    => $user,
            "_password"    => $passwd,
            "_remember_me" => 1,
            "_csrf_token"  => "Connexion", # FIXME
        );
        $url = 'https://en.geneanet.org/connexion/login_check';

        $data = $this->post($url, $args);

        # <p class="error">Erreur de connexion (identifiant/mot de passe). Merci de vérifier les informations saisies. </p>
        $expr = '#<p class="error">(.*)</p>#mi';
        if (preg_match($expr, $data, $values)) {
            # $this->log("# connexion error");
            $this->error = $values[1];
            return false;
        }

        return true;
    }

    public function logout()
    {
        $args = array(
            "nologin"    => 1,
        );
        $url = 'https://en.geneanet.org/';
        $this->post($url, $args);
    }

    public function lastError()
    {
        return $this->error;
    }

    public function post($url, $args)
    {
        return $this->curl->post($url, $args);
    }

    public function get($url)
    {
        return $this->curl->get($url);
    }

    protected function log($msg)
    {
        printf("%s\n", $msg);
    }

    public function setProxy($proxy)
    {
        $this->curl->setProxy($proxy);
    }
}
