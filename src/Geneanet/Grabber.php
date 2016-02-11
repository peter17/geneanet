<?php

namespace Geneanet;

/* geany_encoding=ISO-8859-15 */

class Grabber
{
    
    protected $geneanet;
    protected $delay = 1;
    
    // will force this args in request (url).
    protected $geneanet_args = array(
        'lang' => 'en',
        'templ' => 'mobile'
    );

    public function __construct($geneanet)
    {
        $this->geneanet = $geneanet;
        $this->parser = new GeneanetEntryParser();
        $this->url = new URL();
    }

    public function grabSingle($url)
    {

        $url = $this->url->enforceParams($url, $this->geneanet_args);

        $html = $this->urlGrabCached($url);
        
        if ($html == false) {
            return false;
        }

        $person = $this->parse($html, $url);
        $person->url = $url;

        return $person;
    }

    public function grabDescendants($person, $level = 3, $indent = 0)
    {
        
        # printf("# grabDescendants(%s, %s)\n", $level, utf8_decode($person->name()));

        if ($level == 0) {
            return;
        }

        $unions = $this->grabUnions($person);
        $person->set('unions', $unions);

        foreach ($unions as $union) {
            foreach ($union['childs'] as $child) {
                # printf("  - %s\n", utf8_decode($child->name()));
                #printf("# %s - %s\n",
                #   str_repeat("   - ", $indent),
                #   utf8_decode($child->quickDisplay())
                #	);
                $this->grabDescendants($child, $level-1, $indent+1);
            }
        }
    }

    public function grabAscendants($person, $level = 3)
    {
        
        # printf("# grabParents(%s) %s\n", $level, $person->quickDisplay());

        if ($level == 0) {
            return;
        }

        $parents = $person->parents;
        $_parents = array();
        
        if (isset($parents[0]['url'])) {
            $url = $this->makeUrl($person->url, $parents[0]);
            $person1 = $this->grabSingle($url);
            $_parents[0] = $person1;
            $this->grabAscendants($person1, $level-1);
        }
        if (isset($parents[1]['url'])) {
            $url = $this->makeUrl($person->url, $parents[1]);
            $person1 = $this->grabSingle($url);
            $_parents[1] = $person1;
            $this->grabAscendants($person1, $level-1);
        }
        printf(
            " %s - (%d) %s\n",
            str_repeat("   ", $level),
            $level,
            utf8_decode($person->quickDisplay())
        );

        $person->set('parents', $_parents);

    }
    
    public function grabUnions($person)
    {
        return $this->grabUnionsAndChilds($person);
    }

    public function grabUnionsAndChilds($person)
    {
        
        # printf("# grabUnionsAndChilds(%s) / %s\n", utf8_decode($person->name()), $person->url);

        $unions = $person->unions;
        $_unions = array();

        foreach ($unions as $u) {
            $_unions = array();
            if ($u['url'] == null) {
                continue;
            }
            $url = $this->makeUrl($person->url, $u);
            $_union['self'] = $person;
            $_union['spouse'] = $this->grabSingle($url);
            $_union['childs'] = array();
            
            if (isset($u['childs'])) {
                foreach ($u['childs'] as $c) {
                    $url = $this->makeUrl($person->url, $c);
                    $_union['childs'][] = $this->grabSingle($url);
                }
            }
            
            $_unions[] = $_union;
        }
        
        return $_unions;
    }

    public function grabSiblings($person)
    {
        
        # printf("# grabSiblings(%s) / %s\n", utf8_decode($person->name()), $person->url);

        $list = array();
        $siblings = $person->siblings;

        foreach ($siblings as $s) {
            if ($s['url'] == null) {
                continue;
            }
            $url = $this->makeUrl($person->url, $s);
            $list[] = $this->grabSingle($url);
        }
        
        return $list;
    }

    public function grabHalfSiblings($person)
    {
        
        # printf("# grabHalfSiblings(%s) / %s\n", utf8_decode($person->name()), $person->url);

        $list = array();
        $siblings = $person->half_siblings;

        foreach ($siblings as $s) {
            if ($s['url'] == null) {
                continue;
            }
            $url = $this->makeUrl($person->url, $s);
            $list[] = $this->grabSingle($url);
        }
        
        return $list;
    }

    protected function makeUrl($url, $person)
    {
        return sprintf('%s%s', $this->url->base($url), $person['url']);
    }

    protected function urlGrabCached($url)
    {

        $md5 = md5($url);
        $cache = "var/cache/" . $md5 . '.html';

        if (file_exists($cache)) {
            return file_get_contents($cache);
        }

        $html = $this->urlGrab($url);

        file_put_contents("var/cache/" . $md5 . '.html', $html);

        return $html;
    }

    protected function urlGrab($url)
    {

        # printf("# grab ($url) : %s\n", $url);
        $count=0;
        while (true) {
            $html = $this->geneanet->get($url);
            $this->delay();
            if ($html !== false) {
                if (! $this->checkIncorrect($html)) {
                    return $html;
                }

                echo "# incorrect request : $url\n";

                $count++;
                if ($count > 10) {
                    echo "# giving up for : $url";
                    return false;
                }
                sleep(5);

            }
            sleep(5);
        }

        return $html;
    }

    protected function checkIncorrect($html)
    {
        if (preg_match('#<h1>Incorrect request</h1>#i', $html)) {
            return true;
        }
        return false;
    }

    protected function parse($html, $url)
    {
        return $this->parser->parse($html, $url);
    }

    public function setProxy($proxy)
    {
        $this->geneanet->setProxy($proxy);
    }
    
    protected function delay($sec = null)
    {
        if ($sec == null) {
            $sec = $this->delay;
        }
        sleep($sec);
    }
    
    /* won't go under 1 second between each request */
    public function setDelay($sec)
    {
        if ($sec<1) {
            $sec = 1;
        }
        $this->delay = $sec;
    }
}
