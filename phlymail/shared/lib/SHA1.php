<?php
/**
 * PHP implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS PUB 180-1
 * Version 1.1
 * Copyright 2002 - 2003 Marcus Campbell
 * http://www.tecknik.net/sha-1/
 *
 * This code is available under the GNU Lesser General Public License:
 * http://www.gnu.org/licenses/lgpl.txt
 * Based on the JavaScript implementation by Paul Johnston
 * http://pajhome.org.uk/
 *
 * PHP5 static class implementation by Matthias Sommerfeld <mso@phlylabs.de>
 *
 * @copyright 2002-2003 Marcus Campbeel
 * @copyright 2007-2012 Matthias Sommerfeld <mso@phlylabs.de>
 * @version 1.1.1mod2 2012-05-02 
 */
class SHA1 {

    static function compute($str)
    {
        $x = self::str2blks_SHA1($str);
        $a = 1732584193;
        $b = -271733879;
        $c = -1732584194;
        $d = 271733878;
        $e = -1009589776;
        for ($i = 0; $i < sizeof($x); $i += 16) {
            $olda = $a;
            $oldb = $b;
            $oldc = $c;
            $oldd = $d;
            $olde = $e;
            for ($j = 0; $j < 80; $j++) {
                $w[$j] = ($j < 16) ? $x[$i+$j] : self::rol($w[$j-3] ^ $w[$j-8] ^ $w[$j-14] ^ $w[$j-16], 1);
                $t = self::safe_add(self::safe_add(self::rol($a, 5), self::ft($j, $b, $c, $d)), self::safe_add(self::safe_add($e, $w[$j]), self::kt($j)));
                $e = $d;
                $d = $c;
                $c = self::rol($b, 30);
                $b = $a;
                $a = $t;
            }
            $a = self::safe_add($a, $olda);
            $b = self::safe_add($b, $oldb);
            $c = self::safe_add($c, $oldc);
            $d = self::safe_add($d, $oldd);
            $e = self::safe_add($e, $olde);
        }
        return sprintf("%08s%08s%08s%08s%08s", dechex($a), dechex($b), dechex($c), dechex($d), dechex($e));
    }

    private static function str2blks_SHA1($str)
    {
        $nblk = ((strlen($str)+8) >> 6)+1;
        for ($i = 0; $i < $nblk*16; $i++) $blks[$i] = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $blks[$i >> 2] |= ord(substr($str, $i, 1)) << (24-($i%4)*8);
        }
        $blks[$i >> 2] |= 0x80 << (24-($i%4)*8);
        $blks[$nblk*16-1] = strlen($str)*8;
        return $blks;
    }

    private static function safe_add($x, $y)
    {
        $lsw = ($x & 0xFFFF) + ($y & 0xFFFF);
        $msw = ($x >> 16)+($y >> 16)+($lsw >> 16);
        return ($msw << 16) | ($lsw & 0xFFFF);
    }

    private static function rol($num, $cnt)
    {
        return ($num << $cnt) | self::zeroFill($num, 32-$cnt);
    }

    private static function zeroFill($a, $b)
    {
        $bin = decbin($a);
        $bin = (strlen($bin) < $b) ? 0 : substr($bin, 0, strlen($bin)-$b);
        for ($i = 0; $i < $b; $i++) $bin = '0'.$bin;
        return bindec($bin);
    }

    private static function ft($t, $b, $c, $d)
    {
        if ($t < 20) return ($b & $c) | ((~$b) & $d);
        if ($t < 40) return $b ^ $c ^ $d;
        if ($t < 60) return ($b & $c) | ($b & $d) | ($c & $d);
        return $b ^ $c ^ $d;
    }

    private static function kt($t)
    {
        if ($t < 20) return 1518500249;
        if ($t < 40) return 1859775393;
        if ($t < 60) return -1894007588;
        return -899497514;
    }
}
