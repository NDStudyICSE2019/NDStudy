******************************************
        CLAROLINE 1.11 - README
******************************************

TABLE OF CONTENT :

  1. DESCRIPTION
  2. LICENCE
  3. CREDITS
  4. INSTALL
  5. DOCUMENTATION
  6. CONTACT
  7. NEW FEATURES

1. DESCRIPTION
   ===========

Claroline is an Open Source software based on PHP/MySQL. It's a collaborative
learning environment allowing teachers or education institutions to create and
administer courses through the web.

The system provides group management, forums, document repositories, calendar,
chat, assignment areas, links, user profile administration on a single and
highly integrated package.

Claroline is translated in 35 languages and used by hundreds of institutions
around world. The software was initially started by the University of Louvain
(Belgium) and released under Open Source licence (GPL). Since then, a
community of developers around the world contributes to its development.

2. LICENCE
   =======

Claroline is distributed under the GENERAL PUBLIC LICENSE (GPL) version 2.

See LICENCE.txt


3. CREDITS
   =======

See CREDITS.txt file


4. INSTALL OR UPGRADE
   ==================

See INSTALL.txt file

  PLEASE ENSURE YOU READ THIS FULLY BEFORE PROCEEDING WITH THE INSTALLATION OR 
  THE UPGRADE.


5. DOCUMENTATION
   =============

Complete manuals for trainers and students are available in different languages
at :

  http://www.claroline.net/


6. CONTACT
   =======

        Consortium Claroline
        IPM
        54 Grand-Rue
        1348 Louvain-la-Neuve
        BELGIUM

        email : info@claroline.net
        web   : http://www.claroline.net
        phone : 32 (0) 10 47 88 87
        Fax   : 32 (0) 47 89 39


7. NEW FEATURES
   ============

NEW FEATURES IN CLAROLINE 1.11 SINCE CLAROLINE 1.10
---------------------------------------------------

* See http://sourceforge.net/apps/mediawiki/claroline/index.php?title=Changelog_1.11

NEW FEATURES IN CLAROLINE 1.10 SINCE CLAROLINE 1.9
-------------------------------------------------

* See http://sourceforge.net/apps/mediawiki/claroline/index.php?title=Changelog_1.10

NEW FEATURES IN CLAROLINE 1.9 SINCE CLAROLINE 1.8
-------------------------------------------------

* See http://sourceforge.net/apps/mediawiki/claroline/index.php?title=Changelog_1.9

NEW FEATURES IN CLAROLINE 1.8.11 SINCE CLAROLINE 1.8.0
------------------------------------------------------

* Bug Fixes ; For more details, see the changelog on our documentation wiki :
  http://www.claroline.net/wiki/index.php/Changelog_1.8.x

NEW FEATURES IN CLAROLINE 1.8.0 SINCE CLAROLINE 1.7
-----------------------------------------------------

This new stable version, that comes with a new logo, offers a bunch of interesting new features :

 * A new default layout
 * Module system : build your own Claroline extension
 * User rights and profiles are now completely customizable
 * Classes management improved
 * Personal language selector
 * Course Tools Improvements :
  o Documents : Download all documents in a compressed file
  o Exercises : Import/export your exercises, ...
  o Wiki : Internal search engine
 * Configuration : many new parameters

You'll find more information and screenshots about Claroline 1.8 in our detailed claroline 1.8 page :

 * http://www.claroline.net/doc/en/index.php/Claroline_18

NEW FEATURES IN CLAROLINE 1.7.7 SINCE CLAROLINE 1.7.6
-----------------------------------------------------
* Translation
  * new greek lang file with 8 corrections (since 1.7.5) by Spiros Ioannou
  * the html editor that uses inc/htmlarea/lang/el.js doesn't work with greek
  at all because all translations in el.js are 16-bit (UTF-8) while the other
  site is iso-8859-7 (8bit). The result is seeing garbage on the editor menus,
  buttons etc. I translated el.js with iconv (iconv -f UTF-8 -t ISO8859-7) and
  I send you the correct one that works ok !

* Security Fix
  * Remote inclusion have been discovered in these scripts.
    Platform in register_globals off and/or allow_url_open off are not vulnerable.
    * claroline/auth/extauth/driver/postnuke.inc.php
    * claroline/auth/extauth/driver/mambo.inc.php
    (THANKS TO ZONE-H Team, again)

* Improve Security
  * protect blindness all included file to prevent other injection.

* Bugs
  * Documents and links : directory named 0 (zero) causes claro_delete_file to exit without deleting all files in a directory (bug #478)
  * Wiki : now wiki page title is always case sensitive
  * Wiki : fix some javascript warning 

NEW FEATURES IN CLAROLINE 1.7.6 SINCE CLAROLINE 1.7.5
-----------------------------------------------------

* Translations

  * Czech 100% by Zdenek Machek
     o Claroline in Czech: http://www.claroline.cz (coming soon)
     o Translated for: ZUS Police (http://www.zuspolice.cz)
  * Dutch - fine tuning including the missing items by Klaas Kroeze.
  * Italian - fine tuning by Fausto Barbarito (http://www.nosmet.com)

* New layouts

  * 1 new Layout : clarcom.css, a new corporate CSS designed by Laurent Colet
                   in the context of an internship at Cerdecam Research Center (Brussels)

Fixed bugs and improvements

Bug fix

    * Course settings : Cannot enter URL of the department with the character - in it.
                        (fixed by Marina from St-Etienne France)

Security Fix

Remote inclusion have been discovered in these scripts. Platform in register_globals off and/or allow_url_open off are not vulnerable. (Discovered by beford and Kevin Fernandez "Siegfried", Zone-H Research Team)

 claroline/admin/admin_class_register.php
 claroline/auth/extauth/casProcess.inc.php
 claroline/auth/extauth/driver/*.inc.php
 claroline/exercice/answer_admin.inc.php
 claroline/exercice/exercise_admin.inc.php
 claroline/exercice/question_admin.inc.php
 claroline/exercice/question_list_admin.inc.php
 claroline/exercice/statement_admin.inc.php
 claroline/inc/claro_init_local.inc.php
 claroline/inc/conf/auth.conf.php.dist
 claroline/inc/lib/add_course.lib.inc.php
 claroline/inc/lib/event/init_event_manager.inc.php
 claroline/inc/lib/export_exe_tracking.class.php

NEW FEATURES IN CLAROLINE 1.7.5 SINCE CLAROLINE 1.7.4
-----------------------------------------------------

This version integrates the last security fixes of claroline 1.7. We highly recommend to upgrade to this recent version.

* New translation : Slovenian 100% by Sergej Rinc - http://sergej.rinc.ws

* New layouts : claroffice.css, caucase.css and netscape.css

* Bug fixes :
    * Add user : Deep redesign of the user addition workflow
    * Assigments : Don't update visibility when editing assignment settings
    * Forum : Change the way rank moving is done for forums and categories

* Security fix :
    * A security hole in the file editing
    * A remote inclusion in scorm export tool
    * A securityt bug in password recovery tool

More info here : http://www.claroline.net/wiki/index.php/Changelog_1.7.x

NEW FEATURES IN CLAROLINE 1.7.4 SINCE CLAROLINE 1.7.3
-----------------------------------------------------

* New translation updates : Greek, Italian and Turkish

* Bug Fixes ; For more details, see the changelog on our documentation wiki :
  http://www.claroline.net/wiki/index.php/Changelog_1.7.x


NEW FEATURES IN CLAROLINE 1.7.3 SINCE CLAROLINE 1.7.2
-----------------------------------------------------

* New translation updates : Spanish Latin

* Bug Fixes ; For more details, see the changelog on our documentation wiki :
  http://www.claroline.net/wiki/index.php/Changelog_1.7.x

NEW FEATURES IN CLAROLINE 1.7.2 SINCE CLAROLINE 1.7.1
-----------------------------------------------------

* New translation updates : Arabic, Polish , Bulgarian, Greek, Persian,
                            Spanish Latin, Romanian, Traditional Chinese

* Security and bug Fixes ; For more details, see the changelog on our documentation wiki :
  http://www.claroline.net/wiki/index.php/Changelog_1.7.x

NEW FEATURES IN CLAROLINE 1.7.1 SINCE CLAROLINE 1.7.0
-----------------------------------------------------

* New translation updates : Arabic, Bulgarian, Croatian, Dutch, French, Galician,
                            Indonesian, Persian, Polish and Spanish
* Security and bug Fixes ; For more details, see the changelog on our documentation wiki :
  http://www.claroline.net/wiki/index.php/Changelog_1.7.x

NEW FEATURES IN CLAROLINE 1.7 SINCE CLAROLINE 1.6
-------------------------------------------------

  See http://www.claroline.net/newfeatures17.html

NEW FEATURES IN CLAROLINE 1.6 SINCE CLAROLINE 1.5
-------------------------------------------------

* ASSIGNMENTS. New assignment tool dealing with :

    - Planned work sessions.
    - Automatically close or open assignments uploads.
    - Time planning. (start date and end date)
    - Give a personalized (or automatic) feedback to any work session submission.
    - Work content can be text, file or text and file.

* DOCUMENTS AND LINKS. Two new features:

    - new image gallery
    - Internal search functionality

* EXERCISES. important improvements.

    - time planning. (start date, end date and duration)
    - optional attempts limit for each user.
    - multimedia files. (pictures,documents, flash animations, videos, sounds, ... )
    - flash mp3 player.

* CLASSES. This totally new tool allows platform administrator to
  manage more easily users by grouping them in classes and subclasses on
  your virtual campus. Then classes can be and enrolled to specific
  courses.

* USERS. Massive import with CSV files or classes.

* CONFIGURATION FILES EDITOR. New configuration files editor allowing
  easy customization of the campus and of the tool parameters.

    - Define which values are required for the creation of new courses.
    - Set the disk space limit of the documents and links tool.
    - Select which values are required in user profiles
    - Choose the number of posts displayed per forum page
    - ...

* TRACKING & STATISTICS.

    - Improvements on exercises and forum tracking.
    - Option to erase all statistics of a course.

* TRANSLATION.  New translation files simplify the process to add or
  update language interface of the Claroline system. See documentation
  on http://clarolinet.net: How to translate 1.6 ?

* LAYOUT PERSONALIZATION. Claroline code has been adapted to a more
  intensive use of a CSS stylesheet. This makes it easier, and without
  any code change, for anyone to modify the appearance (color, style,
  logo,..) of new Claroline campus installed. See documentation: Modify
  styles with CSS.

* EXTERNAL AUTHENTICATION SYSTEM. New external authentication system
  provides methods to authenticate user from a significant number of
  systems (LDAP servers, 14 databases from Oracle to ODBC, POP3 servers,
  IMAP servers, vpopmail accounts, RADIUS, SAMBA password files SOAP,
  and various CMS or LMS applications). This feature improves the
  integration of Claroline into your existing computer network
  environment.

* SINGLE SIGN ON (SSO) SYSTEM. Single Sign On (SSO) system enabling
  users, once logged into Claroline, to connect to all other web sites
  session without the need of physically enter repetitive usernames and
  passwords. This feature allows to smoothly associate Claroline into
  your institution Portals.


NEW FEATURES IN CLAROLINE 1.5 SINCE CLAROLINE 1.4
-------------------------------------------------

* LEARNING PATH. A completely new tool has been created allowing course manager
to easily organize course resources for student in a framed learning path.

* WYSIWYG EDITOR. User are now able to create and edit their content on the fly
and store it into documents, posts, announcements, messages, and quizzes.

* SCORM & IMS. Claroline is now compatible with IMS 1.1.2, SCORM 1.2 (minimal
conformance level + optionnal Data Model Element) and SCORM 2004 (basic
conformance). It means the platform is now able to import Content Package
allowing exchange between various Learning Management Systems.

* LDAP AND EXTERNAL AUTHENTICATION. Claroline is now able to connect itself
simultaneously to several external authentication systems, allowing to retrieve
user settings from the outside. An LDAP example based on this technique is
provided.

* LANGUAGES. New languages added. Claroline is now translated in 28 languages
(Arabic, Brazilian, Bulgarian, Catalan, Croatian, Danish, Dutch, English,
French, Galician, German, Greek, Indonesian, Italian, Japanese, Malay, Polish,
Portugese, Russian, Slovenian, Spanish, Swedish, Thai, Turkce, Vietnamese).

* SECURITY IMPROVEMENT. In Claroline 1.4, the init kernel was checking
permission until the course level. The new claroline is more fine grained
checking permission one step further : at tool level.

* COURSE HOME PAGE. New layout, leaving most of the display area to course
manager, allowing him to fill it with his own content.

* COURSE BANNER. Navigation improvement allowing user to jump in a single click
from one tool to another.

* AGENDA. Additional cross course calendar displaying synthetic monthly view of
all the events related to a user.

* DOCUMENT. You can now create and edit HTML document on line. You can also
create hyperlinks among documents. It means the Claroline 'Links' tool is
deprecated. This feature is no taken in charge more completely by the 'document'
tool renamed 'document and links' for the occasion.

* TRACKING AND STATISTICS. Performance and display optimization.

* FORUMS. Email notification of new post in a thread - Forum category categories
are now movable.

* ANNOUNCEMENT. Possibility to send Messages to selected course members and
groups.

* USER HOME PAGE. Once logged users are now directly informs about new course
events since last login.

* GROUP. Chat room for each group has been added.

* COURSE CATEGORIES. The course categories are not limited anymore to a single
level. You can now create subcategory tree as deep as you want to arrange courses.

* PLATFORM ADMINISTRATION. Completely revamped user interface of the
administration section. The phpMyadmin database manager isn't needed anymore.
All the administration is handled through this interface.

* COURSE ENROLLMENT. After long usability tests, the course enrollment device
has been completely changed to make it easier.


NEW FEATURES IN CLAROLINE 1.4 SINCE CLAROLINE 1.3
-------------------------------------------------

The main Claroline improvements are hidden behind the screen. We have totally
recoded the Claroline Kernel to be more stable and more modular.

* SINGLE DB. Claroline is able now to work on a single DB.   The single DB
feature is an option to choose at install step.

* LANGUAGE. New languages added. Claroline is now in 20 languages :   Arab,
Catalan, Crotian, Chinese, Dutch, English, Finnish, French,   German, Galician,
Greek, Hungarian, Italian, Japanese, Polish,   Portugese, Spanish , Swedish,
Tha�, Turkish.

* LAYOUT. New 'liquid layout' spreading on all the window.   Introduction of CSS
system.

* MAIN PAGE. New courses list display in a 'dynamic tree' system.   It allows
the main page to display faster, especially on servers with important number
of course.

* QUIZZ. Totally new quizz tool with 4 different question forms   (multiple
choices / mutiple answers / fill inn / matching). The quizz tool  also allows :
image attachment to questions, questions retrieval from another quizz, random
question list generation.

* DOCUMENT. It automatically detects IMG tag inside HTML file offering  to
upload the corresponding image.

* WEB CHAT. simple chat with archive function,

* FORUMS. Better protection for private forums

* COURSE ENROLLEMENT. Much simplified enrollment interface.

* LOST PASSWORD. Allowing user recover to recover his/her personnal   password

* ASSIGNMENT. New features allowing Course manager to remove,   edit, or hide
assignements send by course students.

* USER. New interface and new features allowing each course manager   to insert
additional information headings inside its own course.   Each course attendee
can then fill these headings to provide the   information.

* ADMIN SECTION. Restore sytem to insert previously backed up courses.  Most
admin tools have been consedirably simplified. Bulk user subribe,  possibility
to deactivate self registration.

* IMPORT / EXPORT : backup and restore for Claroline to Claroline   import, to
prepare IMS and WebCT to Claroline import,

* TRACKING. The ezBoo statistics tool has been removed.  and a complete tracking
system is implemented recording   every user's action on the platform.


NEW FEATURES IN CLAROLINE 1.3 SINCE CLAROLINE 1.2
-------------------------------------------------

* LANGUAGE. New languages added. Claroline is now in 12 languages : Chinese,
English, Finnish, French, German, Italian, Japanese, Polish, Portugese,
Spanish, Swedish and Thai.

* GROUPS. A new comprehensive group tool has been added. It allows to easily
organise student groups, attribute them a tutor and create private forum and
document area for each of them.

* ANNOUNCEMENTS. New annoucements can be sent by mail to students registered
to the course.

* DOCUMENTS. You can now upload several files in one go by compressing them in
a single zip file. Once uploaded the file is automatically uncompressed and
the files structure accurately reproduced.

* COURSE INFO SECTION. An archive function has been added. It allows teacher
to keep an archive copy of its course (useful when one needs   to modify the
course for a new session but wants to keep tracks of the   previous session).

* WEB INSTALL. New functions allowing to upgrade from 1.2 and 1.1 versions to
1.3 has been added.

* ADMIN SECTION. The new PHPMyAdmin 2.3 has been included and admin tools
completely rewritten.


NEW FEATURES IN CLAROLINE 1.2 SINCE CLAROLINE 1.1
-------------------------------------------------

* Claroline can be installed in a server sub-directory.

* UCL local settings have been removed (Faculties, IPM link, Course Program...).

* LANGUAGES. Claroline is now in 7 languages: English, Finnish, French, German,
Italian, Japanese, Spanish. New language adding has been automated. You just
need to add new language directory into claroline/lang dir. It is then available
at Course creation as long as 'Modify Course Info' tool.

* WEB INSTALL. Open browser and go to (...)claroline/install to install new
package.   WATCH OUT! The web install does not manage claroline1.1.1 upgrade to
1.2.0 yet.

* TEXT EDITING. In almost all tools, URLs and emails are converted into links
and new   lines into HTML new lines (tags <br>).

* AGENDA. New layout, clearer view (Month separation and mention of current
day). Language date formatting automated.

* ANNOUNCEMENTS. Can be moved up and down the list through blue arrow.

* DOCUMENTS. '.php' files uploads are renamed '.phps ' (increase security).
Renaming a directory doesn't remove its content comments anymore. Directories
size calculation works with PHP 4.1+ (no problem with previous versions).

* STATS. Install bug fixed (no manual stats settings editing required). Users
registered only once per session per course.

* ADMINISTRATION. Basic administration tool at claroline/admin/ protected   by
'.htaccess' (protection only functional if Apache server). Contains   phpSysInfo
(only functional in Linux servers) and phpMyAdmin 2.2.6.

* TODO. Improved from Admin point of view. You can clasify todos, attribute
them to a developper, check state of progress and inform users that their todo
is taken into account, at what stage and by who.


=========================================================================
                Europe, Belgium, Louvain-la-Neuve - December 15 2008
================================== END ===================================
