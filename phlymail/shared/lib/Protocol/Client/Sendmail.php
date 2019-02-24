<?php
/**
 * Class to send mails through Sendmail
 * @package phlyMail Nahariya 4.0+, Branch MessageCenter
 * @copyright 2003-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 0.2.2 2015-04-10 
 */
class Protocol_Client_Sendmail extends Protocol_Client_Base
{
    // How do we operate? 'classic' for PHP < 4.3.0, else 'modern'
    public $pipemode = 'classic';
    // Init resource handle for connection
    public $sndml = false;

    /**
     * The phm_streaming_sendmail constructor method.
     * @param string Path to sendmail with arguments
     */
    public function __construct($path)
    {
        // Try a fallback, if modern is set, but not possible
        if ($this->pipemode == 'modern') {
            $this->pipemode = (function_exists('proc_open')) ? 'modern' : 'classic';
        }
        if ($path != '') {
            $this->_open($path);
        }
    }

    /**
     * Write to the pipe
     * @param  string  Line of data to put to the pipe
     * @return boolean Returns TRUE on success, FALSE otherwise
     */
    public function put_data_to_stream($line = false)
    {
        if (!is_resource($this->sndml)) {
            return false;
        }
        if (!$line) {
            return false;
        }
        fwrite($this->sndml, str_replace('\r', '', str_replace('\0', '', str_replace(CRLF, LF, $line))));
        return true;
    }

    /**
     * Finishing a mail transfer to the sendmail
     * Use this method, if your application doesn't automatically
     * put the final CRLF.CRLF to the stream after
     * putting all the mail data to it.
     * This method implicitly calls check_success().
     *
     * @return boolean Return state of check_success()
     */
    public function finish_transfer()
    {
        return $this->check_success();

    }

    /**
     * Call this method after putting your last mail line to the stream
     * @return boolean Returns TRUE on success, FALSE otherwise
     */
    public function check_success()
    {
        return true;
    }

    /**
     * Close a previously opened connection
     * Although it doesn't return you something, you can query the state by using
     * get_last_error()
     *
     * @param    void
     * @return   void
     * @access   public
     */
    public function close()
    {
        switch ($this->pipemode) {
            case 'modern':
                if (!feof($this->pipes[1])) {
                    while (!feof($this->pipes[2]) && $line = fgets($this->pipes[2], 4096)) {
                        $this->error .= $line;
                    }
                }
                if (!feof($this->pipes[2])) {
                    while (!feof($this->pipes[2]) && $line = fgets($this->pipes[2], 4096)) {
                        $this->error .= $line;
                    }
                }
                fclose($this->pipes[0]);
                fclose($this->pipes[1]);
                fclose($this->pipes[2]);
                proc_close($this->process);
                if ($this->error) return false;
                $this->error = 'Connection closed';
                break;
            case 'classic':
                pclose($this->sndml);
                $this->error = 'Connection closed';
                break;
        }
        return true;
    }

    /**
     * Open pipe to Sendmail
     *
     * @param    string    Path with arguments
     * @return   boolean   TRUE on success, FALSE otherwise
     * @access   private
     */
    protected function _open($path)
    {
        switch ($this->pipemode) {
            case 'modern':
                $descriptors = array
                        (0 => array('pipe', 'r') // stdin
                        ,1 => array('pipe', 'w') // stdout
                        ,2 => array('pipe', 'w') // stderr
                        );
                $this->process = proc_open($path, $descriptors, $this->pipes);
                if (!is_resource($this->process)) {
                    $this->error = 'Cold not open pipe to '.$path;
                    while (!feof($this->pipes[2]) && $line = fgets($this->pipes[2], 4096)) {
                        $this->error .= $line;
                    }
                    return false;
                }
                $this->sndml = &$this->pipes[0];
                break;
            case 'classic':
                $sndml = popen($path, 'w');
                if (!$sndml) {
                    $this->error = 'Could not open pipe to '.$path;
                    return false;
                }
                $this->sndml = $sndml;
                break;
        }
        return true;
    }
}
