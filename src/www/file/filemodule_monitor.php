<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');

if (user_isloggedin()) {
    $vFilemodule_id = new Valid_UInt('filemodule_id');
    $vFilemodule_id->required();
    if ($request->valid($vFilemodule_id)) {
        $filemodule_id = $request->get('filemodule_id');
        $pm            = ProjectManager::instance();
        $um            = UserManager::instance();
        $userHelper    = new UserHelper();
        $currentUser   = $um->getCurrentUser();
        $frspf         = new FRSPackageFactory();
        $package       = $frspf->getFRSPackageFromDb($filemodule_id);
        $fmmf          = new FileModuleMonitorFactory();
        $historyDao    = new ProjectHistoryDao(CodendiDataAccess::instance());
        $anonymous     = true;
        $performAction = false;
        if ($frspf->userCanRead($group_id, $filemodule_id, $currentUser->getId())) {
            if ($request->get('action') == 'monitor_package') {
                if ($request->valid(new Valid_WhiteList('frs_monitoring', array('stop_monitoring', 'anonymous_monitoring', 'public_monitoring')))) {
                    $action = $request->get('frs_monitoring');
                    switch ($action) {
                        case 'stop_monitoring' :
                            if ($fmmf->isMonitoring($filemodule_id, $currentUser, false)) {
                                $result = $fmmf->stopMonitor($filemodule_id, $currentUser);
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'monitor_turned_off'));
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'no_emails'));
                            }
                            break;
                        case 'public_monitoring' :
                            $anonymous = false;
                        case 'anonymous_monitoring' :
                            if ($anonymous && (!$fmmf->isMonitoring($filemodule_id, $currentUser, false) || $fmmf->isMonitoring($filemodule_id, $currentUser, $anonymous))) {
                                $performAction = true;
                                $fmmf->stopMonitor($filemodule_id, $currentUser);
                            } elseif (!$anonymous && !$fmmf->isMonitoring($filemodule_id, $currentUser, !$anonymous)) {
                                $performAction = true;
                                $historyDao->groupAddHistory("frs_self_add_monitor_package", $filemodule_id, $group_id);
                            }
                            if ($performAction) {
                                $result = $fmmf->setMonitor($filemodule_id, $currentUser, $anonymous);
                                if (!$result) {
                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'insert_err'));
                                } else {
                                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'p_monitored'));
                                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'now_emails'));
                                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'turn_monitor_off'), CODENDI_PURIFIER_LIGHT);
                                }
                            }
                            break;
                        default :
                            break;
                    }
                }
            }

            if ($frspf->userCanAdmin($currentUser, $group_id)) {
                if ($request->valid(new Valid_WhiteList('action', array('add_monitoring', 'delete_monitoring')))) {
                    $action = $request->get('action');
                    switch ($action) {
                        case 'add_monitoring' :
                            $users = array_map('trim', preg_split('/[,;]/', $request->get('listeners_to_add')));
                            foreach ($users as $userName) {
                                if (!empty($userName)) {
                                    $user = $um->findUser($userName);
                                    if ($user) {
                                        $publicly = true;
                                        if ($frspf->userCanRead($group_id, $filemodule_id, $user->getId())) {
                                            if (!$fmmf->isMonitoring($filemodule_id, $user, $publicly)) {
                                                $anonymous = false;
                                                $result = $fmmf->setMonitor($filemodule_id, $user, $anonymous);
                                                if ($result) {
                                                    $historyDao->groupAddHistory("frs_add_monitor_package", $filemodule_id."_".$user->getId(), $group_id);
                                                    $fmmf->notifyAfterAdd($package, $user);
                                                    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'monitoring_added', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                                } else {
                                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'insert_err'));
                                                }
                                            } else {
                                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('file_filemodule_monitor', 'already_monitoring', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                            }
                                        } else {
                                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'user_no_permission', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                        }
                                    } else {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'no_user', array($userName)));
                                    }
                                }
                            }
                            break;
                        case 'delete_monitoring' :
                            $users = $request->get('delete_user');
                            if ($users && !empty($users) && is_array($users)) {
                                foreach ($users as $userId) {
                                    $user = $um->getUserById($userId);
                                    if ($user) {
                                        $publicly = true;
                                        if ($fmmf->isMonitoring($filemodule_id, $user, $publicly)) {
                                            $onlyPublic = true;
                                            $result = $fmmf->stopMonitor($filemodule_id, $user, $onlyPublic);
                                            if ($result) {
                                                $historyDao->groupAddHistory("frs_stop_monitor_package", $filemodule_id."_".$user->getId(), $group_id);
                                                $fmmf->notifyAfterDelete($package, $user);
                                                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor', 'deleted', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                            } else {
                                                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'delete_error', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                            }
                                        } else {
                                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'not_monitoring', array($userHelper->getDisplayName($user->getName(), $user->getRealName()))));
                                        }
                                    }
                                }
                            } else {
                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('file_filemodule_monitor', 'no_delete'));
                            }
                            break;
                        default :
                            break;
                    }
                }
            }

            file_utils_header(array('title' => $Language->getText('file_showfiles', 'file_p_for', $pm->getProject($group_id)->getPublicName())));
            echo $fmmf->getMonitoringHTML($currentUser, $group_id, $filemodule_id, $um, $userHelper);
            file_utils_footer($params);
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'no_permission'));
            $GLOBALS['Response']->redirect('showfiles.php?group_id='.$group_id);
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor', 'choose_p'));
        $GLOBALS['Response']->redirect('showfiles.php?group_id='.$group_id);
    }
} else {
    exit_not_logged_in();
}

?>