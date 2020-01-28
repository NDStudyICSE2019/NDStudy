<!DOCTYPE html>
<!-- 
                                                                                         
    a88888b. dP                            dP oo                   
   d8'   `88 88                            88                      
   88        88 .d8888b. 88d888b. .d8888b. 88 dP 88d888b. .d8888b. 
   88        88 88'  `88 88'  `88 88'  `88 88 88 88'  `88 88ooood8 
   Y8.   .88 88 88.  .88 88       88.  .88 88 88 88    88 88.  ... 
    Y88888P' dP `88888P8 dP       `88888P' dP dP dP    dP `88888P' 

    >>>>>>>>>>> open source learning management system <<<<<<<<<<<

    $Id: work_score.tpl.php 14486 2013-07-03 08:11:29Z zefredz $

-->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/HTML; charset=<?php echo get_locale('charset');?>"  />
        
        <title>
            <?php echo claro_htmlspecialchars ( $this->course->administrativeNumber ); ?> - <?php echo claro_htmlspecialchars ( $this->assignment->getTitle() ); ?>
        </title>
        
        <style>
            /******************************************************************************
                        CLAROLINE TABLES
             ******************************************************************************/

            .claroTable {
                text-align: left;
            }

            .claroTable td,
            .claroTable th {
                vertical-align: middle;
                padding: 2px 5px 2px 5px;
                border-bottom: 1px solid #EDEDED;
            }

            .claroTable .superHeader th,
            .claroTable th.superHeader {
                color: #444;
                background-color: #DDDDDD;
                border-bottom: 2px #444 solid;
                padding: 2px 5px 2px 5px;
                font-weight: bold;
            }

            .claroTable img {
                vertical-align: text-bottom;
            }

            .claroTable .headerY th,
            .claroTable .headerX th, 
            .claroTable thead th {
                color: #444;
                font-weight: bold;
                padding: 2px 5px 2px 5px;
                background-color: #DDDDDD;
                border-bottom: 1px #444 solid;
                text-align: center;
            }

            /* extension of claroTable class for Image Viewer */
            .claroTable tr th.toolbar {
                font-weight: normal;
                padding: 2px 5px 2px 5px;
            }

            .claroTable tr.toolbar th.prev {
                text-align: left;
                font-weight: normal;
                padding: 2px 5px 2px 5px;
            }

            .claroTable tr.toolbar th.title {
                font-weight: bold;
                text-align: center;
                padding: 2px 5px 2px 5px;
            }

            .claroTable tr.toolbar th.next {
                text-align: center;
                font-weight: normal;
                padding: 2px 5px 2px 5px;
            }

            .claroTable td.workingWeek {
                vertical-align: top;
                color: #999;
                padding: 2px 5px 2px 5px;
            }

            .claroTable td.weekEnd {
                vertical-align: top;
                color: #73A244;
                padding: 2px 5px 2px 5px;
            }
            .claroTable tbody tr td.highlight {
                vertical-align: top;
                color: #CD853F;
                padding: 2px 5px 2px 5px;
            }

            .emphaseLine tbody td {
                border-bottom: solid #DDDEBC 1px ;
            }

            .emphaseLine tbody tr:hover {
                background-color: #EDEDED;
            }
        </style>
    </head>
    <body>
        <hgroup>
            <h1><?php echo claro_htmlspecialchars ( $this->assignment->getTitle() ); ?></h1>
            <h2><?php echo get_lang ( 'Scores' ); ?></h2>
        </hgroup>
        <table class="claroTable emphaseLine" width="100%">
            <thead>
                <tr class="headerX">
                    <th><?php echo get_lang ( 'Work title' ); ?> <?php echo get_lang('(last submission)'); ?></th>
                    <th><?php echo get_lang ( 'Author(s)' ); ?></th>
                    <th><?php echo get_lang ( 'Submissions' ); ?></th>
                    <th><?php echo get_lang ( 'Feedbacks' ); ?></th>
                    <th><?php echo get_lang ( 'Max. score' ); ?></th>
                    <th><?php echo get_lang ( 'Min. score' ); ?></th>
                    <th><?php echo get_lang ( 'Avg. score' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $this->scoreList as $score ): ?>
                    <tr>
                        <td><?php echo $score->title; ?></td>
                        <td><?php echo $score->author; ?></td>
                        <td><?php echo $score->submissionCount; ?></td>
                        <td><?php echo $score->feedbackCount; ?></td>
                        <td><?php echo $score->maxScore; ?></td>
                        <td><?php echo $score->minScore; ?></td>
                        <td><?php echo $score->avgScore; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </body>
</html>