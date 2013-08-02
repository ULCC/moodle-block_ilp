<?php

class studentreports_ajax_helper {

    function __construct() {}

    /**
     * Generates the html for an entry. When a new entry is added or edited via AJAX, this provides the html or the added/edited
     * entry. In this case, the first entry is used by the AJAX calls because modified entries are set to display first.
     */
    public function generate_entry($reportfields, $entry, $entry_data, $courseid, $dashboard_reports_tab, $displaysummary, $dontdisplay, $has_courserelated, $comments, $comments_html, $report_id, $student, $readonly = false) {
        global $CFG, $OUTPUT;
        $delete_url = $CFG->wwwroot . "/blocks/ilp/actions/delete_reportentry.ajax.php?report_id={$report_id}&user_id={$entry_data->user_id}&entry_id={$entry->id}&course_id={$courseid}";
        $deletionicon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url("/t/delete"), 'alt'=>get_string('edit')));
        $delete_span = html_writer::tag('span', get_string('delete') . ' ' . $deletionicon, array('data-link'=>$delete_url, 'data-entry'=>$entry->id, 'class'=>'entry-deletion entry-deletion-' . $entry->id));
        $loader_icon = $dashboard_reports_tab->get_loader_icon('delete_entry-loader-' . $entry->id, 'span');

        $delete_span_html = html_writer::tag('div', $delete_span . $loader_icon, array('class'=>'deletion-of-entry'));

        $edit_url = $CFG->wwwroot . "/blocks/ilp/actions/edit_reportentry.ajax.php?report_id={$report_id}&user_id={$entry_data->user_id}&entry_id={$entry->id}&course_id={$courseid}";
        $editionicon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url("/i/edit"), 'alt'=>get_string('edit')));
        $edit_span = html_writer::tag('span', get_string('edit') . ' ' . $editionicon, array(
            'data-link'=>$edit_url, 'data-entry'=>$entry->id, 'data-studentid' => $student->id, 'data-displaysummary' => $displaysummary,
            'class'=>'entry-edition entry-edition-' . $entry->id));
        $loader_icon = $dashboard_reports_tab->get_loader_icon('edit_entry-loader-' . $entry->id, 'span');

        $editionarea = html_writer::tag('div', '', array('class'=>'edit-entry-area edit-entry-area-' . $entry->id));
        $edition_html = html_writer::tag('div', $edit_span . $loader_icon, array('class'=>'edition-of-entry'));
        $edition_html .= $editionarea;

        $addicon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url("/t/add"), 'alt'=>get_string('add')));

        $reportentry_table_fungible = '';
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
' . get_string('addcomment','block_ilp') . $dashboard_reports_tab->get_loader_icon('loader-icon-' . $id_base, 'span') . $addicon . '</span>
<div class="add-form add-form-' . $id_base . '"></div>';

            $reportentry_table = '<div class="sreport-table-container">';
            $reportentry_table_fungible = $this->generate_fungible_table($reportfields, $dontdisplay, $displaysummary, $entry, $entry_data, $student, $id_base, $comments);

        }
        $reportentry_table .= $reportentry_table_fungible;
        $reportentry_table .= html_writer::tag('div', $comments_html, array(
            'class'=>'hiddenelement comments-' . $entry->id . '-' . $student->id, 'id'=>'entry_' . $entry->id . '_container'));
        if (!$readonly) {
            $reportentry_table .= $add_comment_link_html;
            $reportentry_table .= $delete_span_html;
            $reportentry_table .= $edition_html;
        }

        $reportentry_table .= '</div>';
        return $reportentry_table;
    }

    /**
     * Generates the table containing the information for display for an entry.
     */
    public function generate_fungible_table($reportfields, $dontdisplay, $displaysummary, $entry, $entry_data, $student, $id_base, $comments) {
        $reportentry_table_fungible = '<table class="report-entry-table report-entry-table-' . $entry->id . '" columns="2"><tbody>';

        foreach ($reportfields as $field) 	{
            if (!in_array($field->id,$dontdisplay) && (!empty($field->summary) || empty($displaysummary))) {
                $fieldname	=	$field->id."_field";
                $reportentry_table_fungible .= "<tr>";
                $reportentry_table_fungible .= "<td><strong>$field->label: </strong></td>";
                $reportentry_table_fungible .= "<td>";
                $reportentry_table_fungible .= (!empty($entry_data->$fieldname)) ? $entry_data->$fieldname : '&nbsp;';
                $reportentry_table_fungible .= "</td>";
                $reportentry_table_fungible .= "</tr>";
            }
        }

        if (empty($displaysummary)) {
            if (!empty($has_courserelated)) {
                $reportentry_table_fungible .=  "<tr><td><strong>".get_string('course','block_ilp')."</strong>:</td><td>".$entry_data->coursename."</td></tr>";
            }
            $reportentry_table_fungible .= "<tr><td><strong>".get_string('addedby','block_ilp')."</strong>:</td><td>".$entry_data->creator."</td></tr>";
            $reportentry_table_fungible .= "<tr><td><strong>".get_string('date')."</strong>:</td><td>".$entry_data->modified."</td></tr>";
            $comments_toggle = ' <span class="comment_toggle new" data-identifier="' . $entry->id . '-' . $student->id . '">' . get_string('show_comments', 'block_ilp') . '</span>';
            $reportentry_table_fungible .= "<tr><td><strong>$comments_toggle</strong></td>";
            $reportentry_table_fungible .= "<td><span class='numcomments-$id_base'>" . count($comments) . "</span> " . get_string('comments') . "</td></tr>";
        }
        $reportentry_table_fungible .= '</tbody></table>';
        return $reportentry_table_fungible;
    }

    function get_strings_for_ajax_to_dom($identifier) {
        $dom_string = html_writer::tag('div', get_string($identifier, 'block_ilp'), array('class'=>'hiddenelement string-' . $identifier));
        return $dom_string;
    }
}