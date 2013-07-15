<?php

class studentreports_ajax_helper {

    function __construct() {

    }

    function generate_entry($reportfields, $entry, $entry_data, $courseid, $dashboard_reports_tab, $displaysummary, $dontdisplay, $has_courserelated, $comments, $comments_html, $report_id, $student) {
        global $CFG;
        if (!empty($reportfields)) {
            $id_base = 'ajax_com-' . $entry->id;
            $add_comment_link_html = '
<div class="ajax-hidden-details" style="display: none;">
<span class="' . $id_base . '-report_id' . '">'  . $report_id . '</span>
<span class="' . $id_base . '-user_id' . '">' . $entry_data->user_id . '</span>
<span class="' . $id_base . '-selectedtab' . '">' . '' . '</span>
<span class="' . $id_base . '-tabitem' . '">' . '' . '</span>
<span class="' . $id_base . '-course_id' . '">' . ((isset($courseid)) ? $courseid : '')  . '</span>
</div>
<span class="add-comment-ajax" id="' . $id_base . '">
' . get_string('addcomment','block_ilp') . $dashboard_reports_tab->get_loader_icon('loader-icon-' . $id_base, 'span') . '</span>
<div class="add-form add-form-' . $id_base . '"></div>';

            $reportentry_table = '<div class="sreport-table-container">
                    <table class="report-entry-table" columns="2"><tbody>';

            foreach ($reportfields as $field) 	{
                if (!in_array($field->id,$dontdisplay) && ((!empty($displaysummary) && !empty($field->summary)))) {
                    $fieldname	=	$field->id."_field";
                    $reportentry_table .= "<tr>";
                    $reportentry_table .= "<td><strong>$field->label: </strong></td>";
                    $reportentry_table .= "<td>";
                    $reportentry_table .= (!empty($entry_data->$fieldname)) ? $entry_data->$fieldname : '&nbsp;';
                    $reportentry_table .= "</td>";
                    $reportentry_table .= "</tr>";
                }
            }

            if (empty($displaysummary)) {
                if (!empty($has_courserelated)) {
                    $reportentry_table .=  "<tr><td><strong>".get_string('course','block_ilp')."</strong>:</td><td>".$entry_data->coursename."</td></tr>";
                }
                $reportentry_table .=  "<tr><td><strong>".get_string('addedby','block_ilp')."</strong>:</td><td>".$entry_data->creator."</td></tr>";
                $reportentry_table .=  "<tr><td><strong>".get_string('date')."</strong>:</td><td>".$entry_data->modified."</td></tr>";
                $comments_toggle = ' <span class="comment_toggle" data-identifier="' . $entry->id . '-' . $student->id . '">' . get_string('show_comments', 'block_ilp') . '</span>';
                $reportentry_table .=  "<tr><td><strong>".get_string('comments')."</strong>:</td><td><span class='numcomments-$id_base'>" . count($comments) . "</span>" . $comments_toggle."</td></tr>";
            }
            $reportentry_table .= '</tbody></table>';
        }
        $reportentry_table .= html_writer::tag('div', $comments_html, array(
            'class'=>'hiddenelement comments-' . $entry->id . '-' . $student->id, 'id'=>'entry_' . $entry->id . '_container'));
        $reportentry_table .= $add_comment_link_html;
        $reportentry_table .= '</div>';
        return $reportentry_table;
    }

    function get_strings_for_ajax_to_dom($identifier) {
        $dom_string = html_writer::tag('div', get_string($identifier, 'block_ilp'), array('class'=>'hiddenelement string-' . $identifier));
        return $dom_string;
    }
}