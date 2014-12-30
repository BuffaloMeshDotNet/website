iPMWiki skin

PART 1: Preface

The iPMWiki Skin enables an IPhone layout for PMWiki. It uses the IWebKit WebApp Framework from iwebkit.net

Send any questions, comments to moolder@gmx.net.

If you use the skin, please send me a shout or some feedback!
Knowing that my code is used is enough payment for me :)


PART 2: skin setup

copy this folder to the pub/skins folder of your pmwiki installation.

--- editing the PMWiki skin configuration:

Probably you want the skin to only load when pmwiki is displayed by an iphone. To accomplish this, I have included the detect_mobile.php from http://www.pmwiki.org/wiki/Cookbook/DetectMobile

- copy detect_mobile.php to the folder "cookbook" directly inside the pmwiki folder.

- change local/config.php:
(In case you don't have that file, see "initial setup tasks" in pmwiki documentation. Shown here is the default value from docs/default_config.php.)

before:

# $Skin = 'pmwiki';

after:

include_once("$FarmD/cookbook/detect_mobile.php");
if(detect_mobile_device()) {
   $Skin = 'ipmwiki'; # iphone mobile skin
} else {
   $Skin = 'pmwiki'; # the default skin
}

- done


PART 3: tips and tricks

- If you prefer a link to "Home" instead of "SideBar", change the commented part in line 25 of ipmwiki.tmpl.


PART 4: what's next

- Basically, I have what I desire. 
- If you have any ideas for the skin, feel free to contact me at moolder@gmx.net





ENJOY
moolder
