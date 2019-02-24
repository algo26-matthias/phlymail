<?php
/**
 * Allows the use of nonces alongside dereferred URIs to prevent misuse
 *
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2013 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.2 2013-01-22 
 */
class DB_Controller_Derefer extends DB_Controller
{
    /**
     * Constructor
     * @since 0.0.1
     */
    public function __construct()
    {
        parent::__construct();
        $this->Tbl['derefer'] = $this->DB['db_pref'].'core_derefer';
    }

    /**
     * Register an URI with the derefer service
     *
     * @param string $uri  URI to register
     * @return mixed  string Nonce of the URI on success, FALSE on failure
     * @since 0.0.1
     */
    public function register($uri)
    {
        if (!basics::isURL($uri) && !basics::isEmail($uri)) {
            return false;
        }
        $nonce = basics::uuid();
        $expires = date('Y-m-d H:i:s', strtotime('+5 days'));

        $sql = 'INSERT INTO '.$this->Tbl['derefer'].' SET `uri`="'.$this->esc($uri).'", `nonce`="'.$this->esc($nonce).'", `expires`="'.$this->esc($expires).'"';
        $qid = $this->query($sql);
        return ($qid) ? $nonce : false;
    }

    /**
     * Map given nonce to URI
     *
     * @param string $nonce Nonce from the system
     * @return mixed  String of the URI found on success, FALSE otherwise
     * @since 0.0.1
     */
    public function map($nonce)
    {
        $this->expire();
        $qh = $this->query('SELECT `uri` FROM '.$this->Tbl['derefer'].' WHERE `nonce`="'.$this->esc($nonce).'"');
        if ($this->numrows($qh)) {
            list ($uri) = $this->fetchrow($qh);
            return $uri;
        }
        return false;
    }

    /**
     * Drop URI by given nonce
     *
     * @param string $nonce  Unique identifer (nonce)
     * @return bool
     * @since 0.0.1
     */
    public function delete($nonce)
    {
        return $this->query('DELETE FROM '.$this->Tbl['derefer'].' WHERE `nonce`="'.$this->esc($nonce).'"');
    }

    /**
     * Expire the records. By default the expiration date is 5 days after creating
     * the nonce <> URI mapping.
     * There's no auto-expiration right now, but since this method is public, a
     * cronjob task could be set up to perform auto-expiry.
     *
     * @since 0.0.2
     */
    public function expire()
    {
        return $this->query('DELETE FROM '.$this->Tbl['derefer'].' WHERE `expires`<=NOW()');
    }
}
