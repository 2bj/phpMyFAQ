<?php
/**
 * Save an existing FAQ record.
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

if ($permission['editbt']) {
    
    // Get submit action
    $submit        = PMF_Filter::filterInputArray(INPUT_POST, array('submit' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    // FAQ data
    $dateStart     = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd       = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question      = PMF_Filter::filterInput(INPUT_POST, 'thema', FILTER_SANITIZE_STRING);
    $categories    = PMF_Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    $record_lang   = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
    $tags          = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active        = 'yes' == PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING) && $permission['approverec'] ? 'yes' : 'no';
    $sticky        = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content       = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords      = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author        = PMF_Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment       = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $record_id     = PMF_Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $solution_id   = PMF_Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision      = PMF_Filter::filterInput(INPUT_POST, 'revision', FILTER_SANITIZE_STRING);
    $revision_id   = PMF_Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed       = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    $date          = PMF_Filter::filterInput(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    
    // Permissions
    $user_permission   = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
    $restricted_users  = ('all' == $user_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
    $group_permission  = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
    $restricted_groups = ('all' == $group_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);
    
    if (isset($submit['submit'][2]) && !is_null($question) && !is_null($categories)) {
        // Preview
        $categoryDataProvider = new PMF_Category_Tree_DataProvider_SingleQuery($LANGCODE);
        $categoryTree         = new PMF_Category_Tree($categoryDataProvider);
        $categoryLayout       = new PMF_Category_Layout(new PMF_Category_Tree_Helper($categoryTree));
       
        $categorylist = '';
        foreach ($categories['rubrik'] as $categoryId) {
            $categoryPath  = $categoryDataProvider->getPath($categoryId);
            $categorylist .= $categoryLayout->renderBreadcrumb($categoryPath) . '<br />';
        }
?>
    <h2><?php print $PMF_LANG["ad_entry_preview"]; ?></h2>

    <h3><strong><em><?php print $categorylist; ?></em>
    <?php print $question; ?></strong></h3>
    <?php print html_entity_decode($content); ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].PMF_Date::createIsoDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].' '.$author; ?></p>

    <form action="?action=editpreview" method="post">
    <input type="hidden" name="id"                  value="<?php print $record_id; ?>" />
    <input type="hidden" name="thema"               value="<?php print $question; ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print $content; ?>" />
    <input type="hidden" name="lang"                value="<?php print $record_lang; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $keywords; ?>" />
    <input type="hidden" name="tags"                value="<?php print $tags; ?>" />
    <input type="hidden" name="author"              value="<?php print $author; ?>" />
    <input type="hidden" name="email"               value="<?php print $email; ?>" />
    <input type="hidden" name="sticky"              value="<?php print (!is_null($sticky) ? $sticky : ''); ?>" />
<?php
        foreach ($categories['rubrik'] as $key => $_categories) {
            print '    <input type="hidden" name="rubrik['.$key.']" value="'.$_categories.'" />';
        }
?>

    <input type="hidden" name="solution_id"         value="<?php print $solution_id; ?>" />
    <input type="hidden" name="revision"            value="<?php print $revision_id; ?>" />
    <input type="hidden" name="active"              value="<?php print $active; ?>" />
    <input type="hidden" name="changed"             value="<?php print $changed; ?>" />
    <input type="hidden" name="comment"             value="<?php print $comment; ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $user_permission; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $restricted_users; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $group_permission; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $restricted_groups; ?>" />
    <input type="hidden" name="date"    value="<?php print $date; ?>" />
    <p align="center"><input type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
    } elseif (isset($submit['submit'][1]) && !is_null($question) && !is_null($categories)) {
        
        // Save entry
        print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
        $logging = new PMF_Logging();
        $logging->logAdmin($user, 'Saved record ' . $record_id);
        
        if ('yes' == $revision) {
            // Add current version into revision table
            $revision = new PMF_Faq_Revision();
            $revision->creat(array('id' => $record_id, 'lang' => $record_lang));
            $revision_id++;
        }
        
        $recordData = array(
            'id'            => $record_id,
            'lang'          => $record_lang,
            'revision_id'   => $revision_id,
            'active'        => $active,
            'sticky'        => (!is_null($sticky) ? 1 : 0),
            'thema'         => html_entity_decode($question),
            'content'       => html_entity_decode($content),
            'keywords'      => $keywords,
            'author'        => $author,
            'email'         => $email,
            'comment'       => (!is_null($comment) ? 'y' : 'n'),
            'date'          => empty($date) ? date('YmdHis') : str_replace(array('-', ':', ' '), '', $date),
            'dateStart'     => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000'),
            'dateEnd'       => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959'),
            'linkState'     => '',
            'linkDateCheck' => 0);

        // Create ChangeLog entry
        $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $record_lang, $revision_id);

        // save or update the FAQ record
        $faqRecord = new PMF_Faq_Record();
        if ($faq->isAlreadyTranslated($record_id, $record_lang)) {
            $faqRecord->update($record_id, $recordData);
        } else {
            $faqRecord->create($recordData);
            $record_id = $faqRecord->getRecordId();
        }

        if ($record_id) {
            print $PMF_LANG['ad_entry_savedsuc'];
            link_ondemand_javascript($record_id, $record_lang);
?>
    <script type="text/javascript">
    <!--
    $(document).ready(function(){
        setTimeout(function() {
            window.location = "index.php?action=view";
            }, 5000);
        });
    //-->
    </script>       
<?php
        } else {
            print $PMF_LANG['ad_entry_savedfail'].$db->error();
        }
        
        // Insert the tags
        $tagging = new PMF_Tags();
        if ($tags != '') {
            $tagging->saveTags($record_id, explode(',', $tags));
        } else {
            $tagging->deleteTagsFromRecordId($record_id);
        }

        // Add record permissions
        $faqUser = new PMF_Faq_User();
        $faqUser->update($record_id, array('user_id' => $restricted_users));
        if ($groupSupport) {
            $faqGroup = new PMF_Faq_Group();
            $faqGroup->update($recordId, array('group_id' => $restricted_groups));
        }
        
        // Loop the categories
        $categoryUser      = new PMF_Category_User();
        $categoryGroup     = new PMF_Category_Group();
        $categoryRelations = new PMF_Category_Relations();
        $categoryRelations->setLanguage($record_lang);
        foreach ($categories['rubrik'] as $categoryId) {
            $categoryData = array(
                'category_id'   => $categoryId,
                'category_lang' => $categoryRelations->getLanguage(),
                'record_id'     => $record_id,
                'record_lang'   => $record_lang);
            // delete category relations
            $categoryRelations->delete($categoryId);
            // save or update the category relations
            $categoryRelations->create($categoryData);
            
            // Add user permissions
            $userPermission = array(
                'category_id' => $categoryId,
                'user_id'     => $restricted_users);
            $categoryUser->update($categoryId, $userPermission);
            
            // Add group permission
            $groupPermission = array(
                'category_id' => $categoryId,
                'group_id'    => $restricted_groups);
            $categoryGroup->update($category, $group_permission);
        }
    } elseif (isset($submit['submit'][0])) {
        
        $faqRecord = new PMF_Faq_Record();
        $logging   = new PMF_Logging();
        $logging->logAdmin($user, 'Deleted record ' . $record_id);

        $path = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . PMF_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR . $record_id . '/';
        if (@is_dir($path)) {
            $do = dir($path);
            while ($dat = $do->read()) {
                if ($dat != "." && $dat != "..") {
                    unlink($path . $dat);
                }
            }
            rmdir($path);
        }
        
        $faq->deleteRecord($record_id, $record_lang);
        //$faqRecord->delete($record_id);
        print $PMF_LANG['ad_entry_delsuc'];
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}