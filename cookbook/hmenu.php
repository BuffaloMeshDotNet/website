<?php

/* This script adds a new %hmenu% wikistyle that can be applied to
   unordered lists, and will cause them to be displayed according to 
   "Drop-Down Menus, Horizontal Style" from A List Apart
   (http://www.alistapart.com/articles/horizdropdowns/)
*/

SDV($WikiStyle['hmenu'],array('apply'=>'block','class'=>'hmenu'));

$HTMLStylesFmt['hmenu'] = "
  ul.hmenu {
    margin: 0; 
    padding: 0;
    list-style: none;
    width: 150px;
    border-bottom: 1px solid #ccc;
  }
  ul.hmenu li { position: relative; }
  ul.hmenu li ul { 
    margin: 0;
    padding: 0;
    list-style: none;
    width: 150px;
    position: absolute; 
    left: 148px; top: -1px;
    display: none;
    border-bottom: 1px solid #ccc;
  }
  ul.hmenu li {
    text-decoration: none;
    color: #777;
    background: #fff;
    padding: 5px;
    border: 1px solid #ccc;
    border-bottom: 0;
  }
  ul.hmenu li:hover ul, ul.hmenu li.over ul { display:block; }
  /* Fix IE.  Hide from IE Mac.\*/
  * html ul.hmenu li ul { top:0px; left:143px; }
  /* End */
";

$HTMLHeaderFmt['hmenu'] = '
  <script language="javascript"><!--
    hMenu = function() {
      if (document.all) {
        for(k=0;k<document.all.length;k++) {
          navRoot = document.all[k];
          if (navRoot.className!="hmenu") continue;
          for (i=0; i<navRoot.childNodes.length; i++) {
            node = navRoot.childNodes[i];
            if (node.nodeName=="LI") {
              node.onmouseover=function() {
                this.className+=" over";
              }
              node.onmouseout=function() {
                this.className=this.className.replace(" over", "");
              }
            }
          }
        }
      }
    }
    window.onload=hMenu;
  --></script>';

?>

