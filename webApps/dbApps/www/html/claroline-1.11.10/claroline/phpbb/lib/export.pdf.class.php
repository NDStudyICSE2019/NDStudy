<?php

// $Id: export.pdf.class.php 14229 2012-08-06 08:22:10Z zefredz $
/**
 * CLAROLINE
 *
 * Script export topic/forum in PDF
 *
 * @version 1.9 $Revision: 14229 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright (C) 2001 The phpBB Group
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Dimitri Rambout <dim@claroline.net>
 *
 * @package CLFRM
 *
 */
require_once( get_path ( 'incRepositorySys' ) . '/lib/thirdparty/tcpdf/tcpdf.php' );

class exportPDF extends export
{

    private function change_img_url_for_pdf ( $str )
    {
        $pattern = '/(.*?)<img (.*?)src=(\'|")(.*?)url=(.*?)=&(.*?)(\'|")(.*?)>(.*?)/is';

        if ( !preg_match ( $pattern, urldecode ( $str ), $matches ) )
        {
            return $str;
        }

        if ( count ( $matches ) != 10 )
        {
            return $str;
        }

        if ( is_download_url_encoded ( $matches[ 5 ] ) )
        {
            $matches[ 5 ] = download_url_decode ( $matches[ 5 ] );
        }
        
        $matches[ 5 ] = get_conf ( 'rootWeb' ) . 'courses/' . claro_get_current_course_id () . '/document' . $matches[ 5 ];
        //$replace = strip_tags( $matches[1] ) . '<img ' . /*$matches[2] .*/ ' src="' . $matches[5] .'" ' . /*$matches[8] .*/ '>' . strip_tags( $matches[9] );
        $replace = strip_tags ( $matches[ 1 ] ) . '<img src="' . $matches[ 5 ] . '" >' . strip_tags ( $matches[ 9 ] );

        return $replace;
    }

    private function createPDF ( $title )
    {
        $this->pdf = new TCPDF ( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

        $this->pdf->SetTitle ( claro_utf8_encode ( $title, get_conf ( 'charset' ) ) );
        $this->pdf->SetSubject ( claro_utf8_encode ( $title, get_conf ( 'charset' ) ) );

        //set margins
        $this->pdf->SetMargins ( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
        $this->pdf->SetHeaderMargin ( PDF_MARGIN_HEADER );
        $this->pdf->SetFooterMargin ( PDF_MARGIN_FOOTER );

        //set auto page breaks
        $this->pdf->SetAutoPageBreak ( TRUE, PDF_MARGIN_BOTTOM );

        //set image scale factor
        $this->pdf->setImageScale ( PDF_IMAGE_SCALE_RATIO );

        $this->pdf->setPrintHeader ( false );

        return true;
    }

    public function export ()
    {
        $postsList = $this->loadTopic ( $this->getTopicId () );

        $topicInfo = get_topic_settings ( $this->getTopicId () );

        $this->createPDF ( $topicInfo[ 'topic_title' ] );
        $this->pdf->AddPage ();

        $htmlContent = '<p>' . "\n"
            . '<table cellspacing="0" cellpadding="2" border="1">' . "\n"
            . '<tbody>' . "\n"
            . '<tr>' . "\n"
            . '<th colspan="2" style="font-weight: bold; background-color: #EDF1E3; color: #669933; border-bottom: 1px solid #96BB7A;">' . claro_utf8_encode ( $topicInfo[ 'topic_title' ] ) . '</th>'
            . '</tr>' . "\n"
        ;



        foreach ( $postsList as $post )
        {
            $htmlContent .= '<tr>' . "\n"
                . '<td style="width: 150px; background-color: #EEEEEE;">' . "\n"
                . '<div style="font-weight: bold;">' . claro_utf8_encode ( $post[ 'firstname' ] . ' ' . $post[ 'lastname' ], get_conf ( 'charset' ) ) . '</div>' . "\n"
                . '<small>' . claro_html_localised_date ( get_locale ( 'dateTimeFormatLong' ), datetime_to_timestamp ( $post[ 'post_time' ] ) ) . '</small>' . "\n"
                . '</td>' . "\n"
                //.   '<td>' . claro_parse_user_text( $this->change_img_url_for_pdf( $post['post_text'] ) ) . '</td>' . "\n"
                /* DON'T SUPPORT IMAGES FOR THE MOMENT */
                . '<td style="width: 354px;">' . claro_utf8_encode ( claro_parse_user_text ( strip_tags ( $post[ 'post_text' ] ) ), get_conf ( 'charset' ) ) . '</td>' . "\n"
                . '</tr>' . "\n"
            ;
        }

        $htmlContent .= '</tbody>' . "\n"
            . '</table>' . "\n"
            . '</p>'
        ;

        //exit( claro_utf8_decode($htmlContent) );

        $this->pdf->writeHTML ( $htmlContent, true, 0, true, 0 );

        switch ( $this->output )
        {
            case 'screen' :
                {
                    $this->pdf->Output ( claro_utf8_encode ( $topicInfo[ 'topic_id' ] . '_' . $topicInfo[ 'topic_title' ] . '.pdf' ), 'D' );
                }
                break;
            default :
                {
                    $path = get_conf ( 'rootSys' ) . get_conf ( 'tmpPathSys' ) . '/forum_export/';
                    claro_mkdir ( $path );
                    $this->pdf->Output ( $path . claro_utf8_encode ( replace_dangerous_char( $topicInfo[ 'topic_id' ] . '_' . $topicInfo[ 'topic_title' ] ) . '.pdf' ), 'F' );
                }
                break;
        }

        return true;
    }

}
