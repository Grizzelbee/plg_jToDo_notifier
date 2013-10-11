<?php
/**
 * @copyright	Copyright (C) 2005 - 2012 by Hanjo Hingsen, All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @version     1.0.2
 * @history     
        V1.0.2, 2012-12-05, Hanjo
            [!] Das LastViditDate wurde nicht pro Projekt ausgelesen und dadurch wurden
                Neue und aktualisierte ToDos falsch alarmiert.

        V1.0.1, 2012-11-28, Hanjo
            [+] Überfällige ToDos werden alarmie

        V1.0.0, 2012-11-14, Hanjo
            [+] Erste Version
            [+] Alarmiert neue und aktualisierte ToDos
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgUserJTODO_Notifier extends JPlugin
{
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
    }
    
    function onUserLogin($user, $options)
    {
        // run this only on FrontEnd-Login, not on Backend-Login
        if ( JFactory::getApplication()->isAdmin() ) {
            return;  
        }

        // Load the profile data from the database.
        $db = JFactory::getDbo();
        // Get UserId 
        $thisUserId = intval(JUserHelper::getUserId($user['username']));
      
        $query = $db->getQuery(true);
        $query->select('max(todo.inserted) as lastInsert, max(todo.updated) as lastUpdate, proj.name, visit.lastvisitdate');
        $query->from('#__jtodo_todos        AS todo');
        $query->join('', '#__jtodo_projects AS proj  ON (todo.fk_project=proj.id)');
        $query->join('', '#__jtodo_visits   AS visit ON (todo.fk_project=visit.fk_project)');
        $query->where('todo.published = 1');
        $query->where('proj.published = 1');
        $query->where('visit.juserid  = '.(int)$thisUserId );
        $query->group('proj.name');
        $db->setQuery( $query ); 
        $projects = $db->loadObjectList(); 

        //$db->setQuery( 'SELECT lastvisitdate FROM #__jtodo_visits where juserid='.(int)$thisUserId );
        //$lastUserVisit = $db->loadResult();

        foreach ($projects as $project)
        {
            // Feature one: Leave an Information, that a Projectpage has new Entries
            if ( $project->lastInsert >= $project->lastvisitdate ) {
              JError::raiseNotice( 1000, JText::_( sprintf($this->params->get( 'MSG_NEW_DATA', 'Error reading Param: MSG_NEW_DATA' ), $project->name) ));
            } else {
                // Feature two: Leave an Information, that a Projectpage has been updated
                if ( $project->lastUpdate >= $project->lastvisitdate ) {
                  JError::raiseNotice( 1000, JText::_( sprintf($this->params->get( 'MSG_UPDATED', 'Error reading Param: MSG_UPDATED' ), $project->name )));
                }
            }
        }

        $query = $db->getQuery(true);
        $query->select('proj.name, todo.name AS todo, todo.targetdate');
        $query->from('#__jtodo_todos        AS todo');
        $query->join('', '#__jtodo_projects AS proj ON (todo.fk_project=proj.id)');
        $query->where('todo.published = 1');
        $query->where('proj.published = 1');
        $query->where('todo.status    = 0');
        $query->where('todo.targetdate <= CURRENT_DATE');
        $query->order('proj.name');
        $db->setQuery( $query ); 
        $projects = $db->loadObjectList(); 
        
        foreach ($projects as $project)
        {
            // Feature three: Leave an Information, that a todo is overdue
              JError::raiseNotice( 1000, JText::_( sprintf($this->params->get( 'MSG_OVERDUE', 'Error reading Param: MSG_OVERDUE' ), $project->todo, $project->name) ));
        }
	}
}