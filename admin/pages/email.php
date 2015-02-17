<?php
/* Copyright © 2014 TheHostingTool
 *
 * This file is part of TheHostingTool.
 *
 * TheHostingTool is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TheHostingTool is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TheHostingTool.  If not, see <http://www.gnu.org/licenses/>.
 */

// Check if called by script
if(THT != 1){die();}

class page {

    public $navtitle;
    public $navlist = array();

    public function __construct() {
        $this->navtitle = "邮件管理";
        $this->navlist[] = array("邮件模板", "email_open.png", "templates");
        $this->navlist[] = array("Mass Emailer", "transmit.png", "mass");
    }

    public function description() {
        return "<strong>邮件中心</strong><br />
        欢迎来到邮件中心。 在这里你可以编辑邮件模板，或者给你的客户发送邮件。<br />";
    }

    public function content() { # Displays the page
        global $main, $style, $db;

        switch($main->getvar['sub']) {

            case "templates": #email templates
                if($_POST) {
                    foreach($main->postvar as $key => $value) {
                        if($value == "" && !$n) {
                            $main->errors("请把所有需要填写的内容填满!");
                            $n++;
                        }
                    }
                    if(!$n) {
                        $db->query("UPDATE `<PRE>templates` SET
                                   `subject` = '{$main->postvar['subject']}',
                               `content` = '{$main->postvar['content']}'
                               WHERE `id` = '{$main->postvar['template']}'");
                        $main->errors("Template edited!");
                    }
                }
                $query = $db->query("SELECT * FROM `<PRE>templates` ORDER BY `acpvisual` ASC");
                while($data = $db->fetch_array($query)) {
                    $values[] = array($data['acpvisual'], $data['id']);
                }
                $array['TEMPLATES'] = $main->dropDown("LOL", $values, $dID, 0, 1);
                echo $style->replaceVar("tpl/emailtemplates.tpl", $array);
            break;

            case "mass": #mass emailer
                echo $style->replaceVar("tpl/massemail.tpl");
            break;
        }
    }
}
