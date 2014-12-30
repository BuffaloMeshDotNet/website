<?php if (!defined('PmWiki')) exit();
/*  Copyright 2004-2007 Karl Loncarek (karl@loncarek.de)
    This file is part of PmWiki; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.  See pmwiki.php for full details.

History

	* 2005-01-31 first release
	* 2005-02-18 fixed compatibility problem with WikiStyles
	* 2005-02-23 fixed display difference between IE and FireFox (height of .hmenu),
		added "up" and "left" option for changing in which direction the menu opens
	* 2005-03-10 major rewrite to use standard "*" markup
	* 2005-09-30 updated to newest csshover.htc file, added markup description,
		changed CSS a bit to get higher priority
	* 2007-03-28 added (:emenu:) markup - Expanded menu, seprator is now also possible within main menu (should not be used in main hmenu)
  * 2007-01-19 (MikeShanley) fixed the annoying escaped quotes bug.

Available Markup
	(:hmenu:)
		begins a horizontal menu. Available options are up (opens the menu upwards) and left (open menu to the left). These options can be combined, e.g (:hmenu up left:).
	(:vmenu:)
		begins a vertical menu. Same options as with hmenu available.
	(:newmenu:)
		needed for separation of main menu items when using (:hmenu:)
	(:emenu:)
		begin an expanding menu. Availbale options are up (no floating) and left (text is written left of the menu)
	*
		menuitem (same as bullet list)
	* ----
		separator line (should be used after any submenues, otherwise submenu is only accessible when over separaoter line)
	(:menuend:)
		end the menu

*/

$HTMLHeaderFmt[] = '<!--[if IE]><style type="text/css" media="screen">body{behavior:url($PubDirUrl/menus/csshover.htc);}</style><![endif]-->'."\n";
$HTMLHeaderFmt[] = "<link rel='stylesheet' href='$PubDirUrl/menus/cssmenu.css' type='text/css' />\n";

Markup('^menu','<block','/^\\(:(hmenu|vmenu|menuend|newmenu|emenu)\\s?(up|left)?\\s?(up|left)?\\s?:\\)/e',
  "BuildMenu(PSS('$1'),PSS('$2'),PSS('$3'))");

## menu list, same as bulleted list, but with special style, here menutitle
Markup('^menuentry','<^*','/^(\\*+)(.*)$/e',"HorVerMenuModify(PSS('$1'),PSS('$2'))");

## menu list, but with functionality as a separator
Markup('^menuseparator','<^menuentry','/^(\\*+)\\s?----+/e',"HorVerMenuSeparator(PSS('$0'),PSS('$1'))");

function BuildMenu($name,$option1,$option2) {
  global $PubDirUrl, $HTMLHeaderFmt, $HorVerMenu;
  if (($name == 'hmenu') or ($name == 'vmenu') or ($name == 'emenu')) {
    $HorVerMenu = 1;
    $html = "<:block><div id='$name' ";
    if ((!$option2 && $option1) || (($option1 == $option2) && $option1)) {
      $html .="class='menu$option1'";
    }
    elseif ($option1) {
      $html .="class='menuupleft'";
    };
    $html .=">";
    return $html;
  };
  if ($name == 'menuend') {
    if ($HorVerMenu) {
      $HorVerMenu = 0;
    };
    return '<:block></div>';
  };
  return '<:block>';
};

function HorVerMenuModify ($depth,$content) {
  global $HorVerMenu;
  if ($HorVerMenu) {
    if ($depth == '*') {
      $html .= "$depth<span class='menutitle'>$content</span>";
      return $html;
    }
    else
      return "$depth<span class='menuitem'>$content</span>";
  }
  else { /* do not change anything */
    return "$depth$content";
  };
};

function HorVerMenuSeparator($line,$depth) {
  global $HorVerMenu;
  if ($HorVerMenu) {
    return "<:ul,$depth><span class='menuseparator'>-</span>";
  }
  else
    return $line;
};
?>
