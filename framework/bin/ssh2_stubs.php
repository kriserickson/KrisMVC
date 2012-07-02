<?php
/*

This file is never actually used and is just stubs for SSH2 to ensure that no easily detectable errors get created because there are no stubs.

 */

// Note: these numbers have nothing to do with anything.......
define('SSH2_FINGERPRINT_MD5',1);
define('SSH2_FINGERPRINT_SHA1',2);
define('SSH2_FINGERPRINT_HEX',3);
define('SSH2_FINGERPRINT_RAW',4);
define('SSH2_TERM_UNIT_CHARS',5);
define('SSH2_TERM_UNIT_PIXELS',6);
define('SSH2_DEFAULT_TERM_WIDTH',7);
define('SSH2_DEFAULT_TERM_HEIGHT',8);
define('SSH2_DEFAULT_TERM_UNIT',9);
define('SSH2_STREAM_STDIO',10);
define('SSH2_STREAM_STDERR',11);
define('SSH2_DEFAULT_TERMINAL',12);


/**
 * @param string $host
 * @param int $port
 * @param array $methods
 * @param array $callbacks
 * @return resource
 */
function ssh2_connect(string $host, int $port = 22, array $methods = array(), array $callbacks = array()) { };

/**
 * @param resource $session
 * @param string $username
 * @param string $password
 * @return bool
 */
function ssh2_auth_password(resource $session , string $username , string $password) {};

/**
 * @param resource $sftp
 * @param string $from
 * @param string $to
 * @return bool
 */
function ssh2_sftp_rename ( resource $sftp , string $from , string $to ) {};

/**
 * @param resource $sftp
 * @param string $path
 * @return array
 */
function ssh2_sftp_lstat ( resource $sftp , string $path ) {}

/**
 * @param resource $sftp
 * @param string $dirname
 * @param int $mode
 * @param bool $recursive
 * @return bool
 */
function ssh2_sftp_mkdir ( resource $sftp , string $dirname = '', int $mode = 0777 , $recursive = false) {}

/**
 * @param resource $session
 * @param string $local_file
 * @param string $remote_file
 * @param int $create_mode
 * @return bool
 */
function ssh2_scp_send ( resource $session , string $local_file , string $remote_file , int $create_mode = 0644 ) {}

/**
 * @param resource $session
 * @return resource
 */
function ssh2_sftp ( resource $session ) {}


/**
 * @param resource $session
 * @param string $command
 * @param string $pty
 * @param array $env
 * @param int $width
 * @param int $height
 * @param int $width_height_type
 * @return resource
 */
function ssh2_exec ( resource $session , string $command , string $pty ='', array $env = array(), $width = 80 , $height = 25 ,
    $width_height_type = SSH2_TERM_UNIT_CHARS) {}


/**
 * @param resource $channel
 * @param int $streamId
 * @return resource
 */
function ssh2_fetch_stream ( resource $channel , int $streamId ) {}

/**
 * @param resource $sftp
 * @param string $filename
 * @return bool
 */
function ssh2_sftp_unlink ( resource $sftp , string $filename ) {}