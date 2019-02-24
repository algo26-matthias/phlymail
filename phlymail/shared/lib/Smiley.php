<?php
/**
 * Simple class for parsing smiley markup into nice smiley icons
 *
 * @author Matthias Sommerfeld, phlyLabs
 * @copyright 2005-2012 phlyLabs, Berlin http://phlylabs.de/
 * @version 0.2.1 2012-05-02 
 */
class Smiley
{
    // Place identical icons beneath each other, the most common comes last!
    static protected $map = array
            (':D' => '/grin.gif', ':-D' => '/grin.gif'
            ,';)' => '/wink.gif' ,';-)' => '/wink.gif'
            ,'):' => '/sad.gif', ':(' => '/sad.gif', ':-(' => '/sad.gif'
            ,'[:' => '/robosmile.gif', ':]'  => '/robosmile.gif', ':-]' => '/robosmile.gif'
            ,'(8' => '/cool.gif', '8)' => '/cool.gif', '8-)' => '/cool.gif'
            ,'(:' => '/smile.gif', '(-:' => '/smile.gif', ':)' => '/smile.gif', ':-)' => '/smile.gif'
            ,'|-:' => '/blank.gif', ':-|' => '/blank.gif'
            ,':X'  => '/lipssealed.png', ':-X' => '/lipssealed.png'
            ,'O.o' => '/confused.png', 'o.O' => '/confused.png', ':-$' => '/confused.png', ':-S' => '/confused.png'
            ,':-*' => '/kiss.png', ':-*' => '/kiss.png'
            ,'@=' => '/bomb.png'
            ,'<°(((><' => '/fish.gif'
            ,'@}-,-\'-,--' => '/flower.png', '@}->--' => '/flower.png', '@)-}--' => '/flower.png'
            );
    /**
     * Returns a mapping of icon => emoticon. For this to work saitsfyingly
     * place the most common emoticon string resulting in the same icon at the
     * end of self::map - otherwise it will not be returned.
     *
     * @return array
     * @since 0.2.0
     */
    static public function map()
    {
        return array_flip(self::$map);
    }

    /**
     * Parse HTML and replace textual smileys by images
     *
     * @param string $string Your original text, already HTML
     * @param string $basepath Path to the icon directory
     * @return string  Your text with the markup replaced by <img> refs
     */
    static public function parse($string, $basepath)
    {
        foreach (self::$map as $k => $v) {
            $k = htmlspecialchars($k, ENT_COMPAT, 'utf-8'); // Source text is already HTML...
            $string = preg_replace
                    ('!(?<=\<br /\>|\s|\r|\n|^)'.preg_quote($k, '!').'(?=\<br /\>|\s|\r|\n|$)!Um'
                    ,'<img src="'.$basepath.$v.'" alt="'.$k.'" title="'.$k.'" />'
                    ,$string
                    );
        }
        return $string;
    }
}
