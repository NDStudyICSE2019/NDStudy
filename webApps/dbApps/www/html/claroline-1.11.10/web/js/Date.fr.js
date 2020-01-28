// $Id: Date.fr.js 13042 2011-04-04 13:27:30Z abourguignon $
// vim: expandtab sw=4 ts=4 sts=4:

/** 
 * French locale for Date.js library by Henrik Lindqvist
 *
 * @version     1.0 $Revision: 13042 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license      http://www.gnu.org/licenses/lgpl-3.0.txt
 *              GNU LESSER GENERAL PUBLIC LICENSE Version 3.0 or later
 * @package     core.js
 *
 */

(function (d) {

d.i18n['fr'] = 
d.i18n['fr-FR'] = {
  months: {
    abbr: [ 'Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec' ],
    full: [ 'Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre' ]
  },
  days: {
    abbr: [ 'Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam' ],
    full: [ 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi' ]
  },
  week: {   // Used by date pickers
    abbr: 'Sm',
    full: 'Semaine'
  },
  ad: 'AD',
  am: 'AM',
  pm: 'PM',
  gmt: 'GMT',
  z: ':',   // Hour - minute separator
  Z: '',    // Hour - minute separator
  fdow: 1,  // First day of week
  mdifw: 1  // Minimum days in first week
};

})(Date);