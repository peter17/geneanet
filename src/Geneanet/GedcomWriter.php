<?php

namespace Geneanet;

/*
 * usefull information for GEDCOM writing from :
 *  - Pierre FAUQUE :
 *     - http://www.fr-webdev.net/, 
 * 	   - http://www.phpclasses.org/package/7009-PHP-Manage-genealogy-trees-for-a-family.html
 *  - http://ohmi.celeonet.fr/format_gedcom/indexGED.html
 *  - http://fr.geneawiki.com/index.php/Norme_Gedcom
 * 
 * 
 *  usage :
 * 
 * 		$config = new Config();
 * 		$geneanet = new GeneanetServer();
 * 		$grabber = new Grabber($geneanet);
 * 
 * 		...
 * 
 *    	$person = $grabber->grabSingle($url);
 *		$grabber->grabAscendants($person, $level=3);
 * 
 *		$writer = new GedcomWriter($config);
 *		echo $writer->write($person);  # will output GEDCOM data.
 * 
 */

/* geany_encoding=ISO-8859-15 */

class GedcomWriter
{
    protected $cnf; # config file

    public function __construct($config)
    {
        $this->cnf = $config;
        $this->dict = new GedcomDictionary();
        $this->url = new URL();

        # http://nl3.php.net/manual/fr/function.date-default-timezone-set.php
        # print_r($this->cnf);
        if ($this->cnf != null) {
            date_default_timezone_set($this->cnf->get('misc/timezone'));
        }

    }
    
    // build dictionnary
    public function build($person)
    {
        
        if (is_array($person)) {
            return false;
        }

        // step 1 : INDIVIDUALS
        // step 2 : FAMILIES
        // step 3 : NOTES
        // step 4 : SOURCES
        
        $this->buildIndividual($person);
        $this->buildFamilies($person);
        // $this->displayIndividual($person);
    }

    // build individual dictionnary
    public function buildIndividual($person)
    {
        
        if (is_array($person)) {
            return false;
        }

        $this->dictAddIndiv($person);

        foreach ($person->unions as $union) {
            //spouse
            if (isset($union['spouse'])) {
                $this->buildIndividual($union['spouse']);
            }
            
            // childs
            if (isset($union['childs'])) {
                foreach ($union['childs'] as $c) {
                    $this->buildIndividual($c);
                }
            }
        }
    }

    // build families dictionnary
    public function buildFamilies($person)
    {
        
        if (is_array($person)) {
            return false;
        }
        $unions = &$person->get('unions');
        foreach ($unions as $idx => $union) {
            # printf("# buildFamilies(%s)\n", $person->name());
            //print_r($unions);
            //spouse
            $fam_id = null;
            if (isset($union['spouse'])) {
                $this->buildFamilies($union['spouse']);
                $fam_id = $this->dictAddFam($union);

                $unions[$idx]['fams'] = $fam_id;
                $unions[$idx]['source'] = $this->sourcesFindType('spouse', $person);

                // retrive union for 'spouse' and set fam_id.
                $this->setFamId($union['spouse'], $person, $fam_id);
            }
            // childs
            if (isset($union['childs'])) {
                foreach ($union['childs'] as $idx2 => $c) {
                    # print_r($c);
                    if (is_array($c)) {
                        # printf("# setting fams for %s\n", $c['name']);
                        if ($fam_id != null) {
                            $unions[$idx]['childs'][$idx2]['famc'] = $fam_id;
                        }
                        
                    } else {
                        # printf("# setting fams for %s\n", $c->name());
                        if ($fam_id != null) {
                            $unions[$idx]['childs'][$idx2]->famc = $fam_id;
                        }
                        
                    }
                    $this->buildFamilies($c);
                }
            }
        }
        // $unions is by reference so instruction below is not needed.
        # $person->set('unions', $unions);
    }

    public function displayIndividual($person)
    {
        
        if (is_array($person)) {
            return false;
        }

        $famc = '-';
        if (isset($person->famc)) {
            $famc = $person->famc;
        }

        $fams = array();
        foreach ($person->unions as $u) {
            if (isset($u['fams'])) {
                $fams[] = $u['fams'];
            }
        }
        if (count($fams) > 0) {
            $fams = join(',', $fams);
        } else {
            $fams = '-';
        }

        # printf("# displayIndividual(%s / %s / [%s,(%s)])\n", $person->name(), $person->id, $famc, $fams);
        foreach ($person->unions as $union) {
            //spouse
            if (isset($union['spouse'])) {
                $this->displayIndividual($union['spouse']);
            }
            
            // childs
            if (isset($union['childs'])) {
                foreach ($union['childs'] as $c) {
                    $this->displayIndividual($c);
                }
            }
        }
    }

    protected function dictAddIndiv($person)
    {
        if (is_array($person)) {
            return false;
        }

        if (!is_a($person, 'Geneanet\Person')) {
            //print_r($person);
            throw new Exception("program error");
        }
        $id = $this->dict->add(GedcomDictionary::INDIVIDUALS, $person, $person->url);
        $person->id = $id;
        # printf("# dictAddIndiv(%s) : %s\n", $person->name(), $id);
    }

    protected function dictAddFam(&$union)
    {
        if (!isset($union['self'])) {
            return false;
        }

        if (!isset($union['spouse'])) {
            return false;
        }

        if ($union['self']->gender == 'M') {
            $person1 = $union['self'];
            $person2 = $union['spouse'];
        } else {
            $person1 = $union['spouse'];
            $person2 = $union['self'];
        }

        if (is_array($person1)|| is_array($person2)) {
            return false;
        }

        if (!is_a($person1, 'Geneanet\Person') || !is_a($person2, 'Geneanet\Person')) {
            //print_r($person1);
            //print_r($person2);
            throw new Exception("program error");
        }
        
        $key = sprintf("%s+%s", $person1->id, $person2->id);
        $_union = array(
            'HUSB' => $person1,
            'WIFE' => $person2,
         );
        $id = $this->dict->add(GedcomDictionary::FAMILIES, $_union, $key);
        # $union['id'] = $id;
        # printf("# dictAddFam(%s + %s) : id='%s' ; key='%s'\n", $person1->name(), $person2->name(), $id, $key);
        return $id;
    }

    protected function setFamId($person1, $person2, $fam_id)
    {
        $unions = &$person1->get('unions');
        foreach ($unions as $idx => $u) {
            if ($u['name'] == $person2->name()) {
                $unions[$idx]['fams'] = $fam_id;
            }
        }
    }

    public function write($person)
    {
        
        $this->build($person) ;

        $txt = '';
        
        $txt .= $this->header();
        $txt .= $this->individuals($person);
        $txt .= $this->families($person);
        # $txt .= $this->notes($person);
        # $txt .= $this->sources($person);

        $txt .= $this->tailer($person);
        
        return $txt;

    }
    
    protected function header($filename = null)
    {

        $txt = "0 HEAD\n"
    
        // script (program) information : Name, Version, Company,
        . sprintf("1 SOUR %s\n", $this->cnf->get('gedcomwriter/name'))
        . sprintf("2 VERS %s\n", $this->cnf->get('gedcomwriter/version'))
        . sprintf("2 NAME %s\n", $this->cnf->get('gedcomwriter/author'))
        . sprintf("2 CORP %s\n", $this->cnf->get('gedcomwriter/corp'))
        
        // misc : date, time
        . sprintf("1 DATE %s\n", strtoupper(date("j M Y")))
        . sprintf("2 TIME %s\n", date("H:i:s"))
        ;
        
        if ($filename != null) {
            $txt .= sprintf("1 FILE %s\n", $filename);
        }
        
        
        // the submitter
        $txt .= sprintf("1 SUBM @U1@\n")                // pointer to the submitter
        . sprintf("1 COPR %s\n", $this->cnf->get('submitter/name'))

        // file format
        . sprintf("1 GEDC\n")
        . sprintf("2 VERS %s\n", $this->cnf->get('gedcom/version'))
        . sprintf("2 FORM LINEAGE-LINKED\n")
        . sprintf("1 PLAC\n")
        . sprintf("2 FORM %s\n", $this->cnf->get('gedcom/places-format'))
        . sprintf("1 LANG %s\n", $this->cnf->get('gedcom/language'))
        . sprintf("1 CHAR %s\n", $this->cnf->get('gedcom/charset'))

        // The submitter record
        . sprintf("0 @U1@ SUBM\n") // pointer to the submitter
        . sprintf("1 NAME %s\n", $this->cnf->get('submitter/name'))

        ;

        return $txt;

    }

    protected function tailer()
    {
        return "0 TRLR\n";
    }
    protected function individuals($person, $fam_id = null)
    {
        $txt = $this->individual($person, $fam_id);
        
        // unions
          // childs
        // parents
        // siblings
        
        // unions : spouse and childs
        if (!isset($person->unions)) {
            return $txt;
        }

        foreach ($person->unions as $union) {
            # femme ou mari union
            if (isset($union['spouse'])) {
                $txt .= $this->individual($union['spouse'], $fam_id);
            }
            
            if (isset($union['childs'])) {
                foreach ($union['childs'] as $c) {
                    $txt .= $this->individuals($c, $fam_id);
                }
            }
        }
        
        return $txt;
    }
    
    /*
	    attributs actuellement gérés :
	    
		0 @[id]@ INDI 
		1 SEX [gender]
		1 NAME First /Last Name/
		1 BIRT 
			2 DATE [date]
			2 PLAC [place]
		1 DEAT 
			2 DATE [date]
			2 PLAC [place]
		1 FAMC @F1@  (FAMily Child ) : famille 
		1 FAMS @F1@  (FAMily Spouse) : famille par union pour les conjoins (parents ) (WIFE, HUSB) (femme et mari)
		1 NOTE [note]
		1 SOUR [source reference]

	*/

    protected function individual($person)
    {

        if (!is_a($person, 'Geneanet\Person')) {
            return;
        }

        $txt = sprintf("0 @%s@ INDI\n", $person->id);
        $txt .= sprintf("1 NAME %s/%s/\n", $person->first, $person->last);
        $txt .= sprintf("1 SEX %s\n", $person->gender);
        
        $birth = $person->birth;

        if ((trim($birth['place']) != '') || (trim($birth['date']) != '')) {
            $txt .= "1 BIRT\n";
            if (trim($birth['place']) != '') {
                $txt .= sprintf("2 PLAC %s\n", $birth['place']);
            }
            if (trim($birth['date']) != '') {
                $txt .= sprintf("2 DATE %s\n", $birth['date']);
            }
            if (($src = $this->sourcesFindType('birth', $person)) !== false) {
                $txt .= sprintf("2 SOUR %s\n", $src);
            }
        }

        $death = $person->death;
        # printf("# %s\n", $person->name());print_r($birth);print_r($death);
        if ((trim($death['place']) != '') || (trim($death['date']) != '')) {
            $txt .= "1 DEAT\n";
            if (trim($death['place']) != '') {
                $txt .= sprintf("2 PLAC %s\n", $death['place']);
            }
            if (trim($death['date']) != '') {
                $txt .= sprintf("2 DATE %s\n", $death['date']);
            }
            if (($src = $this->sourcesFindType('death', $person)) !== false) {
                $txt .= sprintf("2 SOUR %s\n", $src);
            }
                
        }

        // is the child of family
        if (isset($person->famc)) {
            $txt .= sprintf("1 FAMC @%s@\n", $person->famc);
        }

        foreach ($person->unions as $u) {
            if (isset($u['fams'])) {
                $txt .= sprintf("1 FAMS @%s@\n", $u['fams']);
                # if(($src = $this->sourcesFindType('spouse', $person)) !== false)
                #	$txt .= sprintf("2 SOUR %s\n", $src);
            }
        }
        
        // remove lang=en and templ=mobile from url
        $url = new URL();
        $_url = $url->split(rawurldecode($person->url));
        unset($_url->lang);
        unset($_url->templ);

        $txt .= sprintf("1 NOTE Source Geneanet : %s\n", $_url->build());
        # $txt .= sprintf("1 SOUR %s\n", $person->url);  # attributes starting with _ are ignored.
        
        if (isset($person->notes)) {
            foreach ($person->notes as $n) {
                $txt .= sprintf("1 NOTE %s\n", $n);
            }
        }
        if (isset($person->sources)) {
            foreach ($person->sources as $n) {
                $txt .= sprintf("1 NOTE SRC: %s\n", $n);
            }
        }

        if (isset($person->sources)) {
            $txt .= sprintf("1 SOUR %s\n", join("; ", $person->sources));
        }
        return $txt;
    }

    /* Les FAMilles (unions)
	 * Cet enregistrement est utilisé pour définir toute union légitimée (mariage, PACS, concubinage) entre un
	 * homme (HUSB) et une femme (WIFE). Ce contexte est appelé à contenir leurs éventuels enfants (CHIL).
	 * Une famille peut aussi être monoparentale (union hétérosexuelle,dont un des conjoints est inconnu, à
	 * l’origine d’une naissance). Dans ce cas, le parent connu doit être enregistré avec son enfant (CHIL) et
	 * l’étiquette correspondant à son sexe : HUSB (père) ou WIFE (mère).
	 * 

		0 @F2@ FAM 
			1 HUSB @P4@
			1 WIFE @P3@
			1 CHIL @P6@
			1 CHIL @P1@
			1 MARR 
				2 DATE 28 octobre 1955,
				2 PLAC Saint Denis de Gastines

	 */

    /* extract sub source : birth, death, spouse, ...*/
    protected function sourcesFindType($type, $person)
    {
        if (!isset($person->sources)) {
            return false;
        }
        foreach ($person->sources as $s) {
            if (preg_match("#$type\s*:(.*)#", $s, $values)) {
                return $values[1];
            }
        }
        return false;
    }

    protected function families($person)
    {
        
        $txt = '';
    
        foreach ($this->dict->getall(GedcomDictionary::FAMILIES) as $fam_id => $fam) {
            $txt .= sprintf("0 @%s@ FAM\n", $fam_id);
            $txt .= sprintf("1 HUSB @%s@\n", $fam['HUSB']->id);
            $txt .= sprintf("1 WIFE @%s@\n", $fam['WIFE']->id);

            $found = false;
            foreach ($fam['HUSB']->unions as $u) {
                if (!isset($u['childs'])) {
                    continue;
                }
                foreach ($u['childs'] as $c) {
                    if (!is_a($c, 'Geneanet\Person')) {
                        break;
                    }
                    $txt .= sprintf("1 CHIL @%s@\n", $c->id);
                    $found = true;
                }
            }
            if (!$found) {
                foreach ($fam['WIFE']->unions as $u) {
                    if (!isset($u['childs'])) {
                        continue;
                    }
                    foreach ($u['childs'] as $c) {
                        $txt .= sprintf("1 CHIL @%s@\n", $c->id);
                    }
                }
            }
        }
        return $txt;
    }

    protected function notes($person)
    {
        return '';
    }

    protected function sources($person)
    {
        return '';
    }
    
    // indent line with TABs for easy reading.
    public function pretty($lines, $separator = "\t")
    {
        if (!is_array($lines)) {
            $lines = explode("\n", $lines);
        }
        $txt = '';
        foreach ($lines as $line) {
            if (preg_match('#^\s*(\d+) (.*)#', $line, $values)) {
                $txt .= sprintf("%s%s %s\n", str_repeat($separator, $values[1]), $values[1], $values[2]);
            }
        }

        return $txt;
    }

    // remove leading tabs (opposite of $this->pretty()
    public function unpretty($lines, $separator = "\t")
    {
        if (!is_array($lines)) {
            $lines = explode("\n", $lines);
        }
        $txt = '';
        
        foreach ($lines as $line) {
            if (preg_match('#^\s*(\d+) (.*)#', $line, $values)) {
                $txt .= sprintf("%s %s\n", $values[1], $values[2]);
            }
        }
        
        return $txt;
    }
}
