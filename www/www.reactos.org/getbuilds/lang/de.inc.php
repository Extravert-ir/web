<?php
/*
  PROJECT:    ReactOS Website
  LICENSE:    GNU GPLv2 or any later version as published by the Free Software Foundation
  PURPOSE:    Easily download prebuilt ReactOS Revisions
  COPYRIGHT:  Copyright 2007-2009 Colin Finck <mail@colinfinck.de>
  TRANSLATOR: Colin Finck <mail@colinfinck.de>
			  zehnvor 2013
  
  charset=utf-8 without BOM
*/

	$getbuilds_langres["header"] = '<a href="http://www.reactos.org/">Startseite</a> &gt; ReactOS SVN Trunk-Builds';
	$getbuilds_langres["title"] = "ReactOS Trunk-Builds herunterladen";
	$getbuilds_langres["intro"] = 'Hier können Sie aktuelle aber auch ältere ReactOS-Entwicklerversionen herunterladen, die von unserem <a href="http://www.reactos.org/wiki/index.php/RosBuild">BuildBot</a> erstellt wurden.';
	
	$getbuilds_langres["overview"] = "Übersicht";
	$getbuilds_langres["latestrev"] = "Letzte ReactOS-Revision auf dem SVN-Server";
	$getbuilds_langres["browsesvn"] = "SVN-Server online durchsuchen";
	$getbuilds_langres["buildbot_status"] = "BuildBot-Status";
	$getbuilds_langres["buildbot_web"] = "Details im BuildBot Web-Interface ansehen";
	$getbuilds_langres["browsebuilds"] = "Alle erstellten Builds durchsuchen";
	
	$getbuilds_langres["downloadrev"] = "Eine vorkompilierte ReactOS-Revision herunterladen";
	$getbuilds_langres["js_disclaimer"] = 'Sie müssen JavaScript in Ihrem Browser aktivieren, um die Revisions-Dateiliste zu benutzen.<br>Alternativ können Sie alle vorkompilierten Versionen <a href="%s">hier</a> herunterladen.';
	$getbuilds_langres["showrevfiles"] = "Zeige Dateien von Revision";
	$getbuilds_langres["prevrev"] = "Vorherige Revision";
	$getbuilds_langres["nextrev"] = "Nächste Revision";
	$getbuilds_langres["showrev"] = "Anzeigen";
	$getbuilds_langres["gettinglist"] = "Dateiliste wird geladen";
	$getbuilds_langres["isotype"] = "CD-Image-Typen anzeigen";
	
	$getbuilds_langres["foundfiles"] = "%s Dateien gefunden!";
	$getbuilds_langres["filename"] = "Dateiname";
	$getbuilds_langres["filesize"] = "Größe";
	$getbuilds_langres["filedate"] = "Zuletzt geändert";
	
	$getbuilds_langres["nofiles"] 	 = "Für Revision %s gibt es keine vorkompilierten Dateien! Die letzte verfügbare Revision ist " . $rev;
	$getbuilds_langres["invalidrev"] = "Ungültige Revisionsnummer!";
	
	$getbuilds_langres["rangelimitexceeded"] = "Der Revisionsbereich darf maximal %s Revisionen umfassen!";
	
	$getbuilds_langres["legend"]= "Legende";
	$getbuilds_langres["build_bootcd"] = "<tt>bootcd</tt> - BootCD ISOs können benutzt werden, um ReactOS auf einer Festplatte zu installieren. Danach wird das Medium nicht mehr benötigt. BootCDs eignen sich sehr gut zur Benutzung in einer Virtuellen Maschine(VirtualBox, VMWare, QEMU).";
    $getbuilds_langres["build_livecd"] = "<tt>livecd</tt> - LiveCD ISOs erlauben es, ReactOS ohne Installation direkt von der CD oder dem ISO zu starten. So können Sie ReactOS ganz einfach testen.";
    $getbuilds_langres["build_rel"] = "<tt>-rel</tt> - Release Version, ohne Debugging-Funktionen. (Hinweis: Hier finden Sie nur Vorabversionen, Hauptversionen gibt es auf der Download-Seite!)";
    $getbuilds_langres["build_dbg"] = "<tt>-dbg</tt> - Debugging Version. Diese Versionen können benutzt werden, um Debugmeldungen auszulesen. Dies kann hilfreich sein, wenn Sie einen Fehler melden möchten.";
    $getbuilds_langres["build_dbgwin"] = "<tt>-dbgwin</tt> - Siehe -dbg + zusätzliche Gecko- und Winetests";
	$getbuilds_langres["build_msvc"] = "<strong>-msvc</strong> - Debug version includes debugging information and PDB files, use this version to debug with Windbg.";
	
?>
