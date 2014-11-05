<?php if (!defined('PmWiki')) exit();
/*
    The pagetoc script adds support for automatically generating
    a table of contents for a wiki page.

    Version 2.1.1 (production version; works with PmWiki 2.2.56 or above)
    Requires php 5.3 or above and is compatible with php 5.5

    Copyright 2004-2014 John Rankin (john.rankin@affinity.co.nz)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
*/
SDV($TocSize,'smaller');
SDV($TocFloat,false);
$HTMLStylesFmt['toc'] = "
span.anchor {
	float: left;
	font-size: 60%;
	margin-left: -1em;
	width: 1em;
    position:relative; top:-0.1em;
	text-align: center;
}
span.anchor a { text-decoration: none; }
span.anchor a:hover { text-decoration: underline; }
ol.toc { text-indent:-25px; list-style: none; margin-bottom:5px; }
ol ol.toc { margin-left:-24px; text-indent:-24px; }";
$HTMLStylesFmt['tocf'] = "
div.tocfloat { font-size: $TocSize; margin-bottom: 10px;
    border-top: 1px dotted #555555; border-bottom: 1px dotted #555555;
    padding-top: 5px; 
    width: 38%; float: right; margin-left: 10px; clear: right;
    margin-right:-21px; padding-right: 13px; padding-left: 13px;
    background-color: #eeeeee; }
div.toc { font-size: $TocSize; 
    border: 1px dotted #cccccc;
    background: #f7f7f7;
    margin-bottom: 10px; }
div.toc p { background-color: #f9f6d6;
    padding: 5px;
    border-bottom:  1px dotted #cccccc; }"; 
SDV($ToggleText, array('hide', 'show'));
$HTMLHeaderFmt['toggle'] = "<script type=\"text/javascript\">
function toggle(obj, hide, show) {
    var elstyle = document.getElementById(obj).style;
    var text    = document.getElementById(obj + \"tog\");
    if(!hide) { var hide = \"{$ToggleText[0]}\"; }
    if(!show) { var show = \"{$ToggleText[1]}\"; }
    if (elstyle.display == 'none') {
        elstyle.display = 'block';
        text.innerHTML  = hide;
    } else {
        elstyle.display = 'none';
        text.innerHTML  = show;
    }
}
</script>";

## section cross-references
Markup_e('secref','>nl1','/(`)?(Sec|SEC)\\(([A-Za-z][-.:\w]*)\\)/',
    "CrossReferenceList(\$pagename,\$m[1],\$m[2],\$m[3])");
function CrossReferenceList($pagename,$prefix,$ref,$anchor) {
  global $ReferenceList, $format;
  $txt = PageCrossReference($pagename,$anchor);
  if ($format=='pdf') {
    $anch = "$pagename.$anchor";
    $ReferenceList[$anch] = $txt;
    return "$prefix$ref( $anch )";
  }
  return "[[#$anchor | $txt]]";
}


## in-page cross-references
Markup_e('[[#|#','>nl1','/\[\[#([A-Za-z][-.:\w]*)\s*\|\s+#\]\]/',
    "'[[#'.\$m[1].' | '.PageCrossReference(\$pagename,\$m[1]).']]'");
Markup('[[#|*','<[[|','/\[\[#([A-Za-z][-.:\w]*)\s*\|\s*\*\]\]/',
    '[[#$1 | $1]]');
Markup('[[#|+','<[[|','/\[\[#([A-Za-z][-.:\w]*)\s*\|\s*\+\]\]/',
    '[[#$1 | Back to $1]]');
Markup_e("[^#",'<[[#|#','/\[\^#([A-Za-z][-.:\w]*)\^\]/',
    "PageCrossReference(\$pagename,\$m[1],false)");

# if markup.php is not loaded, we need a link cleaner
SDV($LinkCleanerEnabled, false);
if (!$LinkCleanerEnabled) {
  function isClosure($r) {
    return (is_object($r) && ($r instanceof Closure));
  }
  function cleanLinkText($pagename, $text) {
    global $LinkCleaner;
    foreach ($LinkCleaner as $p => $r) {
      $text = isClosure($r) ? preg_replace_callback($p,$r,$text)
                            : preg_replace($p,$r,$text);
    }
    return $text;
  }
  SDVA($LinkCleaner, array(
    '/`\..*?$/' => '...',
    "/\\{(\\$.*?)\\}/" => '$1',
    "/\\[\\[([^|\\]]+)\\|\\s*(.*?)\\]\\]($SuffixPattern)/" =>
      function ($m) use (&$pagename) { return MakeLink($pagename,$m[1],$m[2],$m[3],'$LinkText'); },
    "/\\[\\[([^\\]]+?)\\s*-+&gt;\\s*(.*?)\\]\\]($SuffixPattern)/" =>
      function ($m) use (&$pagename) { return MakeLink($pagename,$m[2],$m[1],$m[3],'$LinkText'); },
    '/\\[\\[#([A-Za-z][-.:\\w]*)\\]\\]/' => "",
    "/\\[\\[(.*?)\\]\\]($SuffixPattern)/" =>
      function ($m) use (&$pagename) { return MakeLink($pagename,$m[1],NULL,$m[2],'$LinkText'); },
    '/[\\[\\{](.*?)\\|(.*?)[\\]\\}]/' => '$1',
    "/`(($GroupPattern([\\/.]))?($WikiWordPattern))/" => '$1',
    "/$GroupPattern\\/($WikiWordPattern)/" => '$1'
            ));
}

function CrossReference($pagename,$text,$anchor) {
  $r = Shortcut($text,$anchor);
  return trim(cleanLinkText($pagename,$r));
}
function Shortcut($text,$anchor) {
  if (preg_match("/\\[\\[#+$anchor\\]\\]\\n?([^\n]+)/",$text,$match)) {
    return preg_replace("/^[#*!:]+\s*/","",
                preg_replace("/([^!]+)!.+/","$1",$match[1]));
  } else {
    return "<em>$anchor</em> not found";
  }
}

## inter-page cross-references
Markup_e('[[?#|#','>nl1',
    "/\[\[((?:$GroupPattern)[.\/])?($NamePattern)#([A-Za-z][-.:\w]*)\s*\|\s+#\]\]/",
    "'[['.\$m[1].\$m[2].'#'.\$m[3].' | '.PageCrossReference(MakePageName(\$pagename,\$m[1].\$m[2]),\$m[3]).']]'");
function PageCrossReference($page,$anchor,$clean=true) {
  global $PCache;
  if ($PCache[$page]['=preview']) 
      $p['text'] = $PCache[$page]['=preview'];
  else
      $p = RetrieveAuthPage($page,'read',false,READPAGE_CURRENT);
  return $clean ? CrossReference($page,$p['text'],$anchor) :
                  Shortcut($p['text'],$anchor);
}

## [[##visibleanchor]]
SDV($VisibleAnchor,'&sect;');
SDV($VisibleAnchorLinks,false);
SDV($DefaultTocAnchor,'toc');
$RefOrTitle = ($VisibleAnchorLinks) ? 'href' : 'title';

## autonumber anchors
Markup('^!#','<links','/^(!+|Q?:)#(#?)/',"TocAnchor");
   
function TocAnchor($m) {
  global $DefaultTocAnchor;
  static $toccounter;
  return $m[1]."[[#".$m[2].$DefaultTocAnchor. ++$toccounter ."]]";
}

## (:markup:) that excludes heading markup examples
function hmarkupHelper($m) {
  $lead = $m[1];
  $txta = str_replace('`.','',$m[3]);
  $txtb = $m[3];
  return "$lead<:block>" .
    Keep("<table class='markup' align='center'><tr><td class='markup1'><pre>" .
      wordwrap($txta, 70) . "</pre></td></tr><tr><td class='markup2'>") .
    "\n$txtb\n(:divend:)</td></tr></table>\n";
}

Markup('`markup','<markup',
  "/(^|\\(:nl:\\))\\(:markup:\\)[^\\S\n]*\\[([=@])((?:\n`\\.!+.*?)+)\\2\\]/sim",
  "hmarkupHelper");

## page table of contents
$IdPattern = "[A-Za-z][-.:\w]*";
if ($format=='pdf') {
    SDV($DefaultTocTitle,'Contents');
    SDV($TocHeaderFmt,
        '[[#toc]]<tbook:visual markup="bf">$TocTitle</tbook:visual>');
    SDV($RemoteTocFmt,
        '<tbook:visual markup="bf">Contents of [[$Toc(#toc)]]</tbook:visual>');
} else {
    SDV($DefaultTocTitle,'On this page...');
    SDV($TocHeaderFmt,'[[#toc]]<b>$TocTitle</b>');
    SDV($RemoteTocFmt,'<b>On page [[$Toc(#toc)]]...</b>');
}
SDV($NumberToc,true);
SDV($L1TocChar, '.');
SDV($OmitQMarkup,false);

if ($action=="print" || $action=="publish") {
    Markup('[[##','<[[#','/\[\[##([A-Za-z][-.:\w]*)\]\]/','[[#$1]]');
    if ($action=='publish') Markup('toc','>include',
        '/\(:([#\*])?toc(?:-(float|hide))?(?:\s+anchors=(v)isible)?(?:\s+(.*?))?:\)/', '');
    Markup('tocback','directives','/\(:toc-back(?:\s+(.*?))?:\)/','');
    Markup('toggle', 'directives', '/\\(:toggle(\*)?\s*(.*?):\\)/', '');
} else {
    Markup('[[##','<[[#','/\[\[##([A-Za-z][-.:\w]*)\]\]/',
    "anchorHelper");
    Markup('toggle', 'directives','/^(.*?)\\(:toggle(\*)?\s*(.*?):\\)/',
    "toggleHelper");
}
Markup_e('toc','>[[#|#',
    '/\(:([#\*])?toc(?:-(float|hide))?(?:\s+anchors=(v)isible)?(?:\s+(.*?))?(?:\s+(Q))?:\)(.*)$/s', 
    "TableOfContents(\$pagename,\$m[1],\$m[2],\$m[4],\$m[5],\$m[6]).
    TocEntryAnchors(\$m[3],\$m[6])");
SDV($TocBackFmt,'&uarr; Contents');
Markup('tocback','directives','/\(:toc-back(?:\s+(.*?))?:\)/',"TocLinkText");
Markup_e('tocpage','directives','/\(:toc-page\s+(.*?)(?:\s+self=([01]))?:\)/',
    "RemoteTableOfContents(\$pagename,\$m[1],\$m[2])");

function anchorHelper($m) {
  global $RefOrTitle, $VisibleAnchor;
  return Keep("<span class='anchor'>".
    "<a name='".$m[1]."' id='".$m[1]."' $RefOrTitle='#".$m[1]."'>".
    "$VisibleAnchor</a></span>",
    'L');
}

function toggleHelper($m) {
  return ($m[1] ? $m[1] : '<:block>').
    Keep('<div class="tog">'.FmtToggleLinks($m[2], $m[3]).'</div>');
}
function FmtToggleLinks($hidden, $opts) {
  global $ToggleText, $ToggleHideID;
  $opt = ParseArgs($opts);
  $hide = $opt['hide'] ? $opt['hide'] : $ToggleText[0];
  $show = $opt['show'] ? $opt['show'] : $ToggleText[1];
  if ($opt['id']) $id = $opt['id'];
  else return "(:toggle$hidden $opts :)";
  $txt = $hidden ? $show : $hide;
  if ($hidden) $ToggleHideID[] = $id;
  if ($opt['button']) { 
      $tag = 'button';
      $att = 'onclick';
  } else {
      $tag = 'a';
      $att = 'href';
  }
  return 
 "<$tag id='$id"."tog' $att=\"javascript:toggle('$id','$hide','$show');\">$txt</$tag>";
}

function RemoteTableOfContents($pagename,$ref,$self=0) {
    global $TocHeaderFmt,$RemoteTocFmt;
    $oTocHeader = $TocHeaderFmt;
    $TocHeaderFmt = str_replace('$Toc',$ref,$RemoteTocFmt);
    $tocname = MakePageName($pagename,$ref);
    if ($tocname==$pagename && $self==0) return '';
    $tocpage=RetrieveAuthPage($tocname,'read',false);
    $toctext=@$tocpage['text'];
    if (strpos($toctext, '(:nogroupheader:)')===false)
        $toctext = $GLOBALS['GroupHeaderFmt'].$toctext;
    if (preg_match('/\(:([#\*])?toc(?:-(float|hide))?(?:\s+anchors=(v)isible)?(?:\s+(.*?))?(?:\s+(Q))?:\)(.*)$/se',$toctext,$m))
        $toc = str_replace('[[#',"[[$ref#",
            TableOfContents($tocname,$m[1],'page','',$m[5],PSS($m[6])));
    $TocHeaderFmt = $oTocHeader;
    return $toc;
}

function TocLinkText($m) {
    global $TocBackFmt;
    $text = $m[1];
    if ($text) $TocBackFmt = $text;
    return '[[#toc | '.$TocBackFmt.']]';
}

function TocEntryAnchors($visible,$text) {
    global $IdPattern;
    return preg_replace_callback(
               "/\n(!+|Q:)\s*((\[\[#+$IdPattern\]\])|##?)?/",
               function ($m) use ($visible) {
                 return "\n".$m[1].InsertAnchor($visible,$m[1],$m[2]);
               },
               $text);
}

function InsertAnchor($visible,$h,$mark) {
  global $OmitQMarkup, $NumberToc, $L1TocChar;
  static $l1,$l2,$toc1,$toc2;
  if ($h=='Q:' && $OmitQMarkup) return $mark;
  if ($mark=='') $visibility = ($visible=='') ? '#' : '##';
  else $visibility = $mark;
  if ($h=='Q:') return $visibility;
  $r = '';
  $len = strlen($h);
  if ($l1==0) { $l1 = $len; }
  else if ($len!=$l1 && $l2==0) { $l2 = $len; }
#  if ($l1==$len || $l2==$len) $r = $visibility;
  if ($l1==$len) { 
    $toc1++; $toc2 = 0; $r = $visibility;
    if ($NumberToc) $r .= "$toc1$L1TocChar&ensp; ";
  } elseif ($l2==$len) { 
    $toc2++; $r = $visibility;
    if ($NumberToc) $r .= "$toc1.$toc2&ensp; "; 
  }
  return $r;
}

function TableOfContents($pagename,$number,$float,$title,$includeq,$text) {
    global $DefaultTocTitle,$TocHeaderFmt,$IdPattern,$NumberToc,$OmitQMarkup,
        $format,$L1TocChar,$DefaultTocAnchor,$TocFloat,$HTMLHeaderFmt,
        $ToggleText;
    if ($includeq)    $OmitQMarkup = (!$OmitQMarkup);
    if ($float=='float') $TocFloat = (!$TocFloat);
    $l1 = 0; $l2 = 0; $l3 = 0;
    $q = 0;  $prelen = 1; $counter = 0;
    $r = ''; $toc1   = 0; $spacer  = '&nbsp;&nbsp;';
    if (!$title) $title = $DefaultTocTitle;
    $toc = str_replace('$TocTitle',$title,$TocHeaderFmt);
    if ($number=='*') $NumberToc = false;
    elseif ($number=='#') $NumberToc = true;
    $closel = 0;
    if ($format=='pdf') {
        $l = 'tbook:item'; 
        $s = ($NumberToc) ? 'tbook:enumerate' : 'tbook:itemize'; $sc = $s;
        $toc = "<tbook:group class='toc'><tbook:p>$toc</tbook:p>".
            "<$sc><$l>\$List</$l></$s></tbook:group>";
        $flag = '!';
    } elseif ($float=='hide') { return '';
    } else {
        $tocid = ($float=='page') ? 'ptocid' : 'tocid';  // remote toc?
        $toggle = " (<a id=\"{$tocid}tog\" href=\"javascript:toggle('$tocid');\">{$ToggleText[0]}</a>)";
        $l = 'li'; $s = ($NumberToc) ? 'ol' : 'ul'; 
        $sc = "$s class='toc'";
        $f = ($TocFloat) ? 'float' : '';
        $toc = "<div class='toc$f'><p>$toc$toggle</p>" . 
               "<$sc id='$tocid'><$l>\$List</$l></$s></div>";
        $flag = '';
    }
    preg_match_all("/\n(!+|Q?:)\s*(\[\[#+$IdPattern\]\]|#*)([^\n]*)/",$text, $match);
    for ($i=0;$i<count($match[0]);$i++) {
         if ($match[1][$i]==':' || ($match[1][$i]=='Q:' && $OmitQMarkup)) {
            if ($match[2][$i] && $match[2][$i][0]=='#')  $counter++; 
            continue; }
         $len = ($match[1][$i]=='Q:') ? 9 : strlen($match[1][$i]);
         if ($len==9) $q = 1;
         if ($l1==0) { $l1 = $len; $prelen = $l1; }
         if ($len!=$l1 && $l2==0) { $l2 = $len; }
         if ($len!=$l1 && $len!=$l2 && $q==1 && $l3==0) { $l3 = $len; }
         if ($len==$l2 && $l1==9) { $len = $l1; }
         if ($len==$l3) { $len = $l2; }
         if ($len!=$prelen) {
            if ($len==$l1) { $r .= "</$l></$s>"; }
            else if ($len==$l2) { $r .= "<$sc><$l>"; 
                $toc2 = 0;
                $closel = 0;
            }
         }
         if ($len==$l1 || $len==$l2) {
            $prelen = $len;
            if ($len==$l1) {
                $toc1++;
                $tocout = ($NumberToc) ? "$toc1$L1TocChar" : '';
            } else {
                $toc2++;
                $tocout = ($NumberToc) ? "$toc1.$toc2" : '';
            }
            $tocout = ($format=='pdf') ? '' : 
	($NumberToc ? (($toc1<10) ? $spacer : '')."$tocout$spacer" : $tocout);
            $m = preg_replace("/^(\\[\\[#)#/","$1",$match[2][$i]);
            $m = preg_replace("/^#+/",'',$m);
            $t = preg_replace('/%(center|right)%/','',$match[3][$i]);
            if ($closel==1) $r .= "\n<:block></$l><$l>";
            $closel = 1;
            if (strpos($m,'[#')==1) 
                $r .= $tocout.str_replace(']]',
                    ' | '.$flag.CrossReference($pagename,"$m$t",
                    preg_replace("/\[\[#(.*?)\]\]/","$1",$m)).']]',$m);
            else {
                $counter++;
                $r .= $tocout."[[#$DefaultTocAnchor$counter | ".$flag.
                    CrossReference($pagename,"[[#]]$t","").']]';
            }
         }
    }
    if ($prelen==$l2) $r .= "</$l></$s>";
    if ($r!='') $r = str_replace('$List',$r,$toc);
    return $r;
}

?>
