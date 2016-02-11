<?php

namespace Geneanet;

/*
 * 
 *  parse entry from Geneanet genealogy server
 * 
 * 
 */

/* geany_encoding=ISO-8859-15 */

define("SECTION_INFO", "info");
define("SECTION_HALF_SIBLINGS", "half-siblings");
define("SECTION_SIBLINGS", "siblings");
define("SECTION_PARENTS", "parents");
define("SECTION_UNIONS", "unions");
define("SECTION_NOTES", "notes");
define("SECTION_SOURCES", "sources");
define("SECTION_RELATIONSHIPS", "relationships");
define("SECTION_FAMILY_NOTES", "family-notes");
define("SECTION_FAMILY_TREE_PREVIEW", "family-tree-preview");


class GeneanetEntryParser
{

    public function __construct()
    {
        
    }
    
    public function parse($data_html, $url)
    {

        $person = new Person();
        $html = new \simple_html_dom($data_html);

        # incorect request
        if ($h1Tag = $html->find('h1', 1)) {
            # printf("# ERR : incorrect request : '%s'\n", $h1Tag->plaintext);
            if ($h1Tag->plaintext == 'Incorrect request') {
                return false;
            }
        }

        # non "mobile version" page
        /* FIXME Not working, maybe Geneanet change this system ?
        if (!preg_match('#This is the Mobile version of the GeneaNet web site. Click here to go to the regular version.#im', $data_html)) {
            error_log("# ERR : html page not in mobile version ($url)\n");
            return false;
        }
         */
        $div = $html->find('div#tree_content h1,h2,ul,p,table');

        $section = SECTION_INFO;
        foreach ($div as $e) {
            # printf("\n# section : %s / [%s] %s\n", $section, $e->tag, $this->encode($this->strLimit($e->plaintext)));

            if ($e->tag == 'h2') {
                # printf("# %s / H2 : %s\n", $section, $e->plaintext);
                
                # Half-siblings
                if (preg_match('/Half-siblings/i', $e->plaintext)) {
                    # printf("# match half-sibling\n");
                    $section = SECTION_HALF_SIBLINGS;
                } # Siblings
                elseif (preg_match('/Siblings/i', $e->plaintext)) {
                    # printf("# match sibling\n");
                    $section = SECTION_SIBLINGS;
                } # Parents
                elseif (preg_match('/Parents/i', $e->plaintext)) {
                    # printf("# match Parents\n");
                    $section = SECTION_PARENTS;
                } # Spouses and children,
                elseif (preg_match('/Spouses( and children|)/i', $e->plaintext)) {
                    # printf("# match unions\n");
                    $section = SECTION_UNIONS;
                } # notes
                elseif (preg_match('/Notes/i', $e->plaintext)) {
                    # printf("# match notes\n");
                    $section = SECTION_NOTES;
                } elseif (preg_match('/Family Note/i', $e->plaintext)) {
                    # printf("# match Family notes\n");
                    $section = SECTION_FAMILY_NOTES;
                } elseif (preg_match('/Sources/i', $e->plaintext)) {
                    # printf("# match sources\n");
                    $section = SECTION_SOURCES;
                } elseif (preg_match('/Family Tree Preview/i', $e->plaintext)) {
                    # printf("# match Family notes\n");
                    $section = SECTION_FAMILY_TREE_PREVIEW;
                } # else if(preg_match('/Relationships/i', $e->plaintext)){
                #    # printf("# match sources\n");
                #    $section = SECTION_RELATIONSHIPS;
                #}
                
                # default section
                elseif ($section == 'info') {
                    $person = $this->parseInfo($person, $e);
                } # else if($section != 'info') {
                else {
                    # TODO : Family Tree Preview
                    /*
                    error_log(sprintf(
                        "# TODO : GeneanetEntryParser::parse(%s) : section non geree : [%s]:'%s' url=%s",
                        utf8_decode($person->name()),
                        $e->tag,
                        $e->plaintext,
                        $url
                    ));
                     */
                }
            
                # printf("# nouvelle section : %s / H2 : %s\n", $section, $e->plaintext);
                
            } else {
                switch($section){
                    case SECTION_INFO:
                        $person = $this->parseInfo($person, $e);
                        break;
                    case SECTION_SIBLINGS:
                        if ($e->tag == 'p') {
                            break;
                        }

                        $person = $this->parseSiblings($person, $e);
                        break;
                    case SECTION_HALF_SIBLINGS:
                        if ($e->tag == 'p') {
                            break;
                        }
                        $person = $this->parseHalfSiblings($person, $e);
                        break;
                    case SECTION_PARENTS:
                        if ($e->tag == 'p') {
                            break;
                        }
                        $person = $this->parseParents($person, $e);
                        break;
                    case SECTION_UNIONS:
                        if ($e->tag == 'p') {
                            break;
                        }
                        if (preg_match('/Regular Version/', $e->innertext)) {
                            break;
                        }
                        if (@count($person->unions) == 0) {
                            # printf("##  unions : [%s] : %s \n", $e->tag, $e->innertext);
                            $person = $this->parseUnions($person, $e);
                        }
                        break;
                    case SECTION_NOTES:
                        $person = $this->parseNotes($person, $e);
                        break;
                    case SECTION_FAMILY_NOTES:
                        $person = $this->parseFamilyNotes($person, $e);
                        break;
                    case SECTION_FAMILY_TREE_PREVIEW:
                        $person = $this->parseFamilyTreePreview($person, $e);
                        break;
                    case SECTION_SOURCES:
                        $person = $this->parseSources($person, $e);
                        break;
                    default:
                        throw new Exception("you know what ??");
                    break;
                }
            }
        }
        
        return $person;
    } /* parse (html) */


    public function parseUnions($person, $html)
    {

        # printf("# parseUnions(%s) : %s\n", $person->name(), $html->plaintext);
        # printf("code : %s\n", $html->innertext);
        
        /* content :
         * ul
         *   li : union [parents]
         *   ul
         *    li : <img gender> <a href="">child1</a>
         *    li : <img gender> <a href="">child2</a> with ...
         *   /ul
         * /ul
         * 
         * plaintext :
         *  - Married [date], [place], to [person] 1967 (Parents : )  with   
         *    - [person] [date]
         *    - [person] [date]
         * 
         *  exception :
         * 
         *   Married to x x with 
         *    - x x
         */
         
         // TODO : get extra information for unions (date and place).
        
        foreach ($html->find('li') as $li) {
            if (!preg_match('#.*li/ul/li$#', $this->nodePath($li, false))) {
                $union = array(
                    'name'   => null,
                    'url'    => null
                );
                if ($a = $li->find('a', 0)) {
                    $union['name'] = $this->encode($this->replaceNbsp($a->plaintext));
                    $union['url'] = $a->href;
                }

                # childs
                foreach ($li->find("ul li") as $li) {
                    if (!$a = $li->find('a', 0)) {
                        continue;
                    }
                    $img = $li->find('img', 0);
                    if ($img == false) {
                        $gender = null;
                    } else {
                        $gender = strtoupper($img->alt);
                    }
                    $union['childs'][] = array(
                        'name'    => trim($this->encode($this->replaceNbsp($a->plaintext))),
                        'url'     => $a->href,
                        'gender'  => $gender
                     );
                }
                
                $person->push('unions', $union);
            }
        }
        
        return $person;
    }

    public function parseNotes($person, $html)
    {
        if (preg_match('/(Regular Version)|(The Online Family Trees)/', $html->innertext)) {
            return $person;
        }
        if (preg_match('/(Relationship.*Descendant)|(Relationship.*Ancestry)/', $html->plaintext)) {
            return $person;
        }
        if (strlen(trim($html->plaintext)) == 0) {
            return $person;
        }

        $person->push('notes', $this->encode($this->replaceNbsp($html->plaintext)));

        return $person;
    }


    public function parseFamilyNotes($person, $html)
    {
        //error_log(sprintf("# family_notes (%s)", $html->tag));
        if (strlen(trim($html->plaintext)) == 0) {
            return $person;
        }
        //error_log("# TODO : need to complete parseFamilyNotes() method");
        $person->push('family-notes', $this->encode($html->plaintext));
        return $person;
    }

    public function parseFamilyTreePreview($person, $html)
    {
        //error_log(sprintf("# family_tree_preview (%s)", $html->tag));
        if (strlen(trim($html->plaintext)) == 0) {
            return $person;
        }
        // error_log("# TODO : need to complete parseFamilyTreePreview() method");
        $person->push('family-tree-preview', $this->encode($html->plaintext));
        return $person;
    }

    public function parseSources($person, $html)
    {

        # only take care of '<ul>' components
        if ($html->tag != 'ul') {
            return $person;
        }

        if (preg_match('/(Regular Version)|(The Online Family Trees)/', $html->innertext)) {
            return $person;
        }

        foreach ($html->find("li em") as $t) {
            $person->push('sources', $this->encode($t->plaintext));
        }
        return $person;
    }

    public function parseSiblings($person, $html)
    {
        # printf("# parseSiblings() : [%s]\n", $html->tag);

        $count = 0;
        while ($anchor = $html->find('a', $count)) {
            if ($anchor->plaintext == 'Regular Version') {
                return $person;
            }
            $person->push('siblings', array(
              'name' => $this->encode($anchor->plaintext),
              'url'  => $anchor->href));
            $count++;
        }

        return $person;
    }

    public function parseHalfSiblings($person, $html)
    {

        if ($html->tag != 'table') {
            return $person;
        }

        # printf("# parseHalfSiblings() : [%s] / %s : %s\n", $html->tag, $this->nodePath($html), $html->plaintext);

        $half = array();
        
        $count = 0;
        while ($anchor = $html->find('a', $count)) {
            # printf("# half-siblings  $count : %s (%s)\n", $anchor->plaintext, $anchor->href);

            switch ($count){
                case 0:
                case 1:
                    $half['parents'][] = array(
                    'name' => $this->encode($anchor->plaintext),
                    'url'  => $anchor->href
                    );
                    break;

                default:
                    $half['siblings'][] = array(
                    'name' => $this->encode($anchor->plaintext),
                    'url'  => $anchor->href
                    );
                    break;
            }
            
            $count++;
        }

        $person->push('half_siblings', $half);

        return $person;
    }

    public function parseParents($person, $html)
    {
        $count = 0;
        while ($anchor = $html->find('a', $count)) {
            $person->push('parents', array(
              'name' => $this->encode($anchor->plaintext),
              'url'  => $anchor->href));
            $count++;
        }

        return $person;
    }

    public function parseInfo($person, $html)
    {

        # printf("# parseInfo() : [%s]\n", $html->tag);

        # <img src="http://static.geneanet.org/arbre/img/man.png" alt="H" title="H" />&nbsp;
        # <a href="mq31?lang=fr;pz=marc;nz=quinton;ocz=0;templ=mobile;m=P;v=jean+marie+louis+pierre;o=i">Jean Marie Louis Pierre</a>
        # <a href="mq31?lang=fr;pz=marc;nz=quinton;ocz=0;templ=mobile;m=N;v=quinton;o=i">QUINTON</a>
        if ($html->tag == 'h1') {
            # gender (sex)
            if ($img = $html->find('img', 0)) {
                $person->gender = strtoupper($img->title);
            }
            
            # firstname
            if ($img = $html->find('a', 0)) {
                $person->first = trim($this->encode($img->plaintext));
            }
            # lastname
            if ($img = $html->find('a', 1)) {
                $person->last = trim($this->encode($img->plaintext));
            }

        } else {
            $list = $html->find('li');
            
            foreach ($list as $e) {
                $txt = trim(html_entity_decode($this->replaceNbsp($e->plaintext)));

                # Born 8 August 1960 - Saint-Brieuc,22000,France
                if (preg_match('/^Born (.*?) - (.*)/i', $txt, $values)) {
                    $person->birth = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => trim($this->encode($values[2]))
                        );
                } # Born - [place]
                elseif (preg_match('/^Born\s+-\s*(.*)/i', $txt, $values)) {
                    $person->birth = array(
                            'date'  => null,
                            'place' => trim($this->encode($values[1]))
                        );
                } # Born 14 October 1901
                elseif (preg_match('/^Born (.*)/i', $txt, $values)) {
                    $person->birth = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => null
                        );
                } # Died - [place]
                elseif (preg_match('/^(?:Died|Deceased)\s+-\s*(.*)/i', $txt, $values)) {
                    $person->birth = array(
                            'date'  => null,
                            'place' => trim($this->encode($values[1]))
                        );
                } # (?:Died|Deceased) [date] - [place], at ...
                elseif (preg_match('/^(?:Died|Deceased) (.*?) - (.*?) , age at/i', $txt, $values)) {
                    $person->death = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => trim($this->encode($values[2]))
                        );
                } # (?:Died|Deceased) [date] - [place]
                elseif (preg_match('/^(?:Died|Deceased) (.*?) - (.*?)/i', $txt, $values)) {
                    $person->death = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => trim($this->encode($values[2]))
                        );
                } # (?:Died|Deceased) [date] , age at death: 44 years old
                elseif (preg_match('/^(?:Died|Deceased) (.*?) , age at/i', $txt, $values)) {
                    $person->death = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => null
                        );
                } # (?:Died|Deceased)
                elseif (preg_match('/^(?:Died|Deceased)\s*$/i', $txt, $values)) {
                    $person->death = array(
                            'status' => 'died',
                            'date'  => null,
                            'place' => null
                        );
                } elseif (preg_match('/^Age : (.*) years old/i', $txt, $values)) {
                    $person->age = trim($values[1]);
                } elseif (preg_match('/^Age : (.*) /i', $txt, $values)) {
                    $person->age = trim($values[1]);
                } # Baptized 28 August 1960 - Saint-Brieuc,22000,France
                elseif (preg_match('/^Baptized (.*?) - (.*)/i', $txt, $values)) {
                    $person->baptism = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => trim($values[2])
                        );
                } # Baptized 28 August 1960
                elseif (preg_match('/^Baptized (.*?)/i', $txt, $values)) {
                    $person->baptism = array(
                            'date'  => strtolower(trim($this->encode($values[1]))),
                            'place' => null
                        );
                } # Consanguinity : 0.13%
                elseif (preg_match('/^Consanguinity : (.*%)/i', $txt, $values)) {
                    $person->consanguinity = $values[1];
                } else {
                    $person->push('jobs', trim($this->encode($txt)));
                }
            }
        }
        return $person;
    }

    public function replaceNbsp($str)
    {
        $from = array('&nbsp;', '&amp;');
        $replace = array(' ', '&');
        return str_replace($from, $replace, $str);
    }

    public function encode($str)
    {
        return ($str);
        # return utf8_decode($str);
    }

    public function strProper($str)
    {
        $str = str_replace('&nbsp;', ' ', $str);
        return trim(strtr(html_entity_decode($str), "\n", ' '));
    }

    public function strReformat($str)
    {
        return preg_replace("#\n#", '\n', $str);
    }

    public function strLimit($str, $len = 70)
    {
        if (strlen($str) <= $len) {
            return $str;
        }
        return sprintf("%s ...", substr($str, 0, $len));
    }

    public function nodePath($html, $full = true)
    {
        $list = array();
        
        $pTag = $html;
        while ($pTag->tag != 'root') {
            if ($full && isset($pTag->class)) {
                $list[] = sprintf("%s.%s", $pTag->tag, $pTag->class);
            } elseif ($full && isset($pTag->id)) {
                $list[] = sprintf("%s#%s", $pTag->tag, $pTag->id);
            } else {
                $list[] = $pTag->tag;
            }

            $pTag = $pTag->parent;
        }

        return '/' . join('/', array_reverse($list));
    }
}
