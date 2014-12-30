<?php if (!defined('PmWiki')) exit();

/*****************************************************************\
* emenu2.php - intelligent expanding menu's for pmwiki            *
* Copyright (C) 2007  Pieter Wuille                               *
* Some of this code is based on emenu.php by Douglas Stebila.     *
*                                                                 *
* This program is free software; you can redistribute it and/or   *
* modify it under the terms of the GNU General Public License     *
* as published by the Free Software Foundation; either version 2  *
* of the License, or (at your option) any later version.          *
*                                                                 *
\*****************************************************************/

/**
 * This plugin provides expanding menu's for use in a sidebar eg.
 * It  will automatically expand the tree to show the currently visible page,
 * but expansion of any nodes can be controlled through markup.
 *
 * Expandable items will get a '+' prefix, while expanded items will get a
 * '-' prefix. In case these items are WikiLinks, this + or - will be added
 * to the visible part of the link, making it clickable and compatible with
 * the gemini/fixflow skins, which put all links in the sidebar on their own
 * line. There is no limit on the number of indentation levels.
 *
 * A menu is written by using a normal list (using '*'s for indentation)
 * enclosed within (:emenu2:) ... (:emenu2end:) directives.
 * A menu item can be made visible/expanded by writing
 * (:emenu2expand Group.Page:). (:emenu2collapse:) can revert this. The current
 * page is made expanded/visible by default, but this can be reverted with
 * (:emenu2collapse {$FullName}).
 *
 * This plugin is somewhat like TreeMenu (but completely server-side, without
 * javascript, and somewhat like ExpandingMenu (but more intelligent and
 * flexible). It replaces this last one.
 *
 */

$RecipeInfo['ExpandingMenu2']['Version'] = '2007-08-04';

# expand and collapse markup
Markup ('emenu2exp', 'fulltext', '/\\(:emenu2expand ([^:]*):\\)/e',"eMenuExpand(PSS('$1'),1)");
Markup ('emenu2col', '>emenu2exp', '/\\(:emenu2collapse ([^:]*):\\)/e',"eMenuExpand(PSS('$1'),-1)");

# handle emenu's
MarkUp ('emenu2', '<links', '/^\\(:(emenu2|emenu2end):\\)/e',"eMenu('$1','$pagename')");
MarkUp ('emenu2line', '>emenu2', '/^(.*)/e',"eMenuLine(PSS('$1'))");

# handle (:emenu2:) ... (:emenu2end:)
# stores lines in a global variable, and pass these lines to eMenuConvert in the end
function eMenu($pos,$pagename) {
  global $eMenuActive,$eMenuLines;
  if ($pos == 'emenu2') { /* begin */
    if (!$eMenuActive) {
      $eMenuActive=1;
      $eMenuLines=array();
      return;
    }
  }
  if ($pos == 'emenu2end') { /* end */
    if ($eMenuActive) {
      $eMenuActive=0;
      return "<:block><div class='emenu'>".eMenuConvert($eMenuLines,$pagename)."<:block></div>";
    }
  }
  return "<:block>";
}

# add line to global variable, or pass-through if not within a (:emenu2:) construct
function eMenuLine($line) {
  global $eMenuActive,$eMenuLines;
  if ($eMenuActive) {
    array_push($eMenuLines,$line);
  } else { /* pass through */
    return $line;
  }
}

# handle (:emenu2expand:) or (:emenu2collapse:)
# a (:emenu2expand [pagename]:) will cause this page's fullname to be stored
# in a associative array. The value associated with that key is the difference
# between the number of expands and the number of collapses. If this number
# is strictly positive, nodes referring to that page will always be visible
# and expanded.
function eMenuExpand($links,$mode) {
  global $eMenuExpand;
  foreach (split('/ +/',$links) as $link) { /* split in links */
    $pn=ResolvePageName($link); /* resolve them */
    $fn=PageVar($pn,'$FullName'); /* find fullname */
    $eMenuExpand[$fn]+=$mode; /* and change that page's expansion mode */
  }
}

# split code in links and non-links
# the result is an array, containing
#   - array($code, $target, $view) for links
#   - array($code, NULL, NULL) for other parts
function eMenuParseLinks($code,$group) {
  $ret = array();
  $pos=0;
  preg_match_all("/\\[\\[([^\\]]+)\\]\\]/",$code,$matches,PREG_SET_ORDER | PREG_OFFSET_CAPTURE); /* find all links */
  foreach ($matches as $link) { /* loop over all links */
    if ($link[0][1]>$pos) array_push($ret,array(substr($code,$pos,$link[0][1]-$pos),NULL,NULL)); /* add skipped non-link part to result */
    $pos=$link[0][1]+strlen($link[0][0]); /* end position of current link */
    $view=$link[1][0];
    $l=str_replace("~","Profile/",$view); /* handle [[~name]] links */
    $l=str_replace("!","Category/",$l); /* handle [[!category]] links */
    $l=str_replace("(","",$l); /* handle [[(foo.)bar]] links */
    $l=str_replace(")","",$l); 
    $l=str_replace(" ","",$l); /* remove spaces */
    preg_match("/^([^\\.:\\/]*?:)?(?:([^\\.:\\/]*?)([\\.\\/]))?([^\\.:\\/]*?)(?:\\|(.*))?\$/",$l,$lmatch); /* parse link (1:map, 2:group, 3:separator, 4:page, 5:view) */
    if ($lmatch[3]=='/' && $lmatch[1]=='') $view=$lmatch[4];
    $view=preg_replace("/\\(.*?\\)/","",$view); /* remove ( ) from view */
    if ($lmatch[5]!='') $view=$lmatch[5];
    if ($lmatch[1]=='') {
      if ($lmatch[2]=='') $lmatch[2]=$group;
      array_push($ret,array($link[0][0],$lmatch[2].'.'.$lmatch[4],$view));
    } else {
      array_push($ret,array($link[0][0],$lmatch[1].$lmatch[2].'.'.$lmatch[4],$view));
    }
  }
  if (strlen($code)>$pos) array_push($ret,array(substr($code,$pos),NULL,NULL)); /* add final non-link part */
  return $ret;
}

# transform a parse-array (as returned by eMenuParseLinks) back into wiki code
# it is possible to give a prefix, which will be prefixed to the first piece
# in the parse-array. In case this first piece is a link, it will be prefixed
# to the links's visible part ($view)
function eMenuGenLine($parsed,$prefix) {
  $ret='';
  foreach ($parsed as $parse) {
    if ($prefix == '') { 
      $ret .= $parse[0];
    } else {
      if (isset($parse[1])) {
        $ret .= "[[$parse[1]|$prefix$parse[2]]]";
      } else {
        $ret .= "$prefix$parse[0]";
      }
      $prefix='';
    }
  }
  return $ret;
}

# take a number of lines (as given between (:emenu2:) and (:emenu2end:)) and
# drop some of them. 
function eMenuConvert($lines,$pagename) {
  global $eMenuExpand,$eMenuCurrentExpanded;
  $tree = array(); /* internal tree representation; is in array of entries: array($parse,$parent or -1,$expanded,$children,$level) */
  $mapje = array(); /* stack that gives index of all ancestors (maps level to index) */
  $pos = 0;
  $maxlev = 0; /* valid entries in $mapje */
  if (!isset($eMenuCurrentExpanded)) {
    $eMenuCurrentExpanded=1;
    eMenuExpand($pagename,1); /* expand currently viewed page */
  }
  foreach ($lines as $line) {
    $level=strspn($line,"*")+1;
    $spaces=strspn(substr($line,$level-1)," ");
    $parent = -1;
    $lind=substr($line,$level+$spaces-1); /* take visible part of line (no *** and whitespace) */
    if ($level<=$maxlev+1 && isset($mapje[$level-1])) $parent=$mapje[$level-1]; /* find parent */
    $tree[$parent][3]=1; /* out parent has child now */
    $mapje[$level]=$pos; /* we can become ancestor */
    $maxlev=$level;
    $parsed=eMenuParseLinks($lind,$group); /* parse entry itself */
    $tree[$pos] = array($parsed,$parent,0,0,$level-1); /* store new value in tree */
    foreach ($parsed as $link) { /* loop over all parsed parts */
      if (isset($link[1]) && isset($eMenuExpand[$link[1]]) && $eMenuExpand[$link[1]]>0) { /* check if it is a link, and needs expanding */
        $loop=$pos;
        while ($loop>=0) { /* loop over all ancestors */
          $tree[$loop][2]=1; /* make them expanded */
          $loop=$tree[$loop][1]; /* go level up */
        }
        break; /* node already expanded, don't need to check other links anymore */
      }
    }
    $pos++;
  }
  $ret = "";
  foreach ($tree as $line) { /* now loop over tree to see what needs showing */
    if ($line[1]==-1 || $tree[$line[1]][2]==1) { /* if entry has no parent, or parent is expanded */
      $ret .= str_repeat('*',$line[4]); /* put ***'s */
      $pre = ''; /* normally no prefix */
      if ($line[3]==1 && $line[2]==1) $pre="'''-''' "; /* unless children & expanded: - */
      if ($line[3]==1 && $line[2]==0) $pre="'''+''' "; /* unless children & !expanded: + */
      $ret .= eMenuGenLine($line[0],$pre)."\n"; /* generate menu line */
    }
  }
  PRR(); /* instruct pmwiki to reprocess our output (as it contains newline, requires re-splitting, ...) */
  return $ret;
}

?>

