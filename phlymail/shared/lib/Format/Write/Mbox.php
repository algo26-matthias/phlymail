<?php
/**
 * This class is meant to handle writing to and reading from MBOX files as
 * specified in RFC 4155 (@see http://www.ietf.org/rfc/rfc4155.txt for details).
 *
 * To add some confusion, let me point you to:
 * http://homepages.tesco.net./~J.deBoynePollard/FGA/mail-mbox-formats.html
 *
 * This is no real driver to work on those files as a storage backend for
 * phlyMail, but more for importing from or exporting to these mbox files.
 *
 * This class is stream based, which means, that you should be easily able to
 * work well with larger mails.
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage Email handler
 * @copyright 2006-2012 phlyLabs, Berlin, http://phlylabs.de
 * @version 0.1.1 2012-05-02 
 */
class Format_Write_Mbox {
    /**
     * This public property tells you, were exactly in the mbox file we
     * currently are.
     * The property is most useful, when querying it just after a read process
     * has finished. The value can then be passed to subsequent calls to
     * read_init() for continued reading of the mbox file.
     *
     * @var int Current offset, Default: 0
     */
    public $offset = 0;

    /**
     * Used for signalling EOF (end of file) on read processes.
     * read_line() returns false, when the end of a mail has been reached. To
     * additionally tell you, whether the end of the mbox has been reached, too,
     * query this flag - on EOF this will evaluate to true.
     *
     * @var bool
     */
    public $eof = false;

    /**
     * All errors, that appeared during the lifetime of an instance of this
     * class, separated by newlines (0x0A).
     *
     * @var unknown_type
     */
    public $error = false;

    /**
     * Private variables. None of your business!
     */
    private $mode = 'idle';
    private $handle = false;
    private $lastline = '';

    /**
     * Initialises the read operation.
     *
     * @param string  Full path to the mbox file
     *[ @param int  Offset where to start reading from; Default 0]
     * @return array  Status array, containing these keys:
     *     status  bool  Whether the first line was indeed a From_ line
     *     format  string  Can currently only be "default"; in case of unrecognized formats this will hold "unknown"
     *     from  string  Holds the envelope sender, if any. Don't rely on it!
     *     received  string  Intended to be the Received: Date and Time as stated in RFC 2822. Don't rely on it!
     * @since 0.0.1
     */
    public function read_init($path, $offset = 0)
    {
        if ($this->mode != 'idle' && $this->mode != 'read'
                || !file_exists($path) || !is_readable($path)) {
            $this->error .= 'Either in wrong mode or no access to file'.LF;
            return array('status' => false, 'format' => false, 'from' => '', 'received' => '');
        }
        $this->handle = fopen($path, 'r');
        if (!$this->handle) {
            $this->error .= 'Failed to open file'.LF;
            return array('status' => false, 'format' => false, 'from' => '', 'received' => '');
        }
        if ($offset) fseek($this->handle, $offset, SEEK_SET);
        if (feof($this->handle)) {
            $this->error .= 'EOF reached'.LF;
            $this->eof = true;
            return array('status' => false, 'format' => false, 'from' => '', 'received' => '');
        }
        $line = explode(chr(0x20), fgets($this->handle, 4096), 3);
        if (feof($this->handle)) {
            $this->error .= 'EOF reached'.LF;
            $this->eof = true;
            return array('status' => false, 'format' => false, 'from' => '', 'received' => '');
        }
        if (empty($line) || $line[0] != 'From' || count($line) < 3) {
            $this->error .= 'Either you pointed me to a wrong place in the mbox or this is none'.LF;
            return array('status' => false, 'format' => 'unknown', 'from' => '', 'received' => '');
        }
        $this->mode = 'read';
        return array('status' => true, 'format' => 'default', 'from' => $line[1], 'received' => $line[2]);
    }

    public function read_line()
    {
        if ($this->mode != 'read') {
            $this->error .= 'Wrong workmode, call read_init() first'.LF;
            return false;
        }
        if (feof($this->handle)) {
            $this->eof = true;
            $this->mode = 'idle';
            fclose($this->handle);
            $this->handle = false;
            return false;
        }
        $line = fgets($this->handle, 4096);
        $check = explode(chr(0x20), $line, 3);
        if (($this->lastline == LF || $this->lastline == CRLF) && !empty($check)
                && $check[0] == 'From' && count($check) == 3) {
            $this->mode = 'idle';
            fclose($this->handle);
            $this->handle = false;
            return false;
        }
        $this->offset = ftell($this->handle);
        $this->lastline = $line;
        return $line;
    }

    /**
     * Inits an append process
     *
     * @param string $path Full path to the mbox file
     * @param string $sender Original sender of the mail
     * @param string $received Original Received: time in RFC2822 format
     * @return bool TRUE on success, FALSE on failure
     * @since 0.0.1
     */
    public function append_init($path, $sender = 'MAILER-DAEMON', $received = false)
    {
        $bp = dirname($path);
        if (($this->mode != 'idle' && $this->mode != 'write')
                || !file_exists($bp) || !is_dir($bp) || !is_writable($bp)) {
            $this->error .= 'Either in wrong mode or no access to file'.LF;
            return false;
        }
        $this->mode = 'write';
        $this->handle = fopen($path, 'a');
        if (!$this->handle) {
            $this->error .= 'Failed to open file'.LF;
            return false;
        }
        if (!$received) $received = date('r');

        fputs($this->handle, 'From '.$sender.' '.$received.LF);
        return true;
    }

    public function append_line($string)
    {
        if ($this->mode != 'write' || false === $this->handle) {
            $this->error .= 'Either in wrong mode or no access to file'.LF;
            return false;
        }
        // This is due to limitations in the format itself, unfortunately breks messages :(
        $string = preg_replace('!^From !', '>From ', $string);

        return fputs($this->handle, rtrim($string).LF);
    }

    public function append_finalize()
    {
        fputs($this->handle, LF);
        fclose($this->handle);
        $this->handle = false;
        return true;
    }

    /**
     * This method is meant to create a file name, which should work well on different platforms.
     * This is achieved by removing all characters, which could potentially lead to confusion under either
     * Windos based OSes or Linux based ones (like \ or /).
     *
     * @param string $name The base name of the MBOX file
     * @param string $suff Pass FALSE to prevent adding a file type suffix or a string you wish to get added
     *               to the basename (e.g. 'mbox' for <basename>.mbox)
     */
    public function simplify_mbox_name($name, $suff = 'mbox')
    {
        $name = preg_replace('![\x00-\x08\x0b\x0c\x0e-\x1F-\x2F\x3A-\x40\x7B-\x9F]!u', '_', trim($name));
        $name = preg_replace('!(_)+!', '_', $name);
        return ($name.(false != $suff ? '.'.$suff : ''));
    }
}
