<blockquote><?php echo claro_parse_user_text( $this->exercise->getDescription() ); ?></blockquote>
<ul style="font-size:small;">
    <li><?php echo get_lang( 'Exercise type' ) . '&nbsp;: ' . ( $this->exercise->getDisplayType() == 'SEQUENTIAL' ? get_lang( 'One question per page (sequential)' ) : get_lang( 'On an unique page' ) ); ?></li>
    <li><?php echo get_lang( 'Random questions' ) . '&nbsp;: ' . ( $this->exercise->getShuffle() ? get_lang( 'Yes' ) : get_lang( 'No' ) ); ?></li>
    <?php if ( $this->exercise->getShuffle() > 0 ): ?>
        <li><?php echo get_lang( 'Reuse same shuffle' ) . '&nbsp;: ' . ( $this->exercise->getUseSameShuffle() ? get_lang( 'Yes' ) : get_lang( 'No' ) ); ?></li>
    <?php endif; ?>
</ul>
<div class="collapsible collapsed">
    <a href="#" class="doCollapse"><?php echo get_lang( 'More information' ); ?></a>
    <div class="collapsible-wrapper">
        <ul id="moreInformation" style="font-size:small;">
            <li><?php echo get_lang( 'Start date' ) . '&nbsp;: ' . claro_html_localised_date( $this->dateTimeFormatLong, $this->exercise->getStartDate() ); ?></li>
            <li><?php echo get_lang( 'End date' ) . '&nbsp;: ' . ( !is_null( $this->exercise->getEndDate() ) ? claro_html_localised_date( $this->dateTimeFormatLong, $this->exercise->getEndDate() ) : get_lang( 'No closing date' ) ); ?></li>
            <li><?php echo get_lang( 'Time limit' ) . '&nbsp;: ' . ( $this->exercise->getTimeLimit() > 0 ? claro_disp_duration( $this->exercise->getTimeLimit() ) : get_lang( 'No time limitation' ) ); ?></li>
            <li><?php echo get_lang( 'Attempts allowed' ) . '&nbsp;: ' . ( $this->exercise->getAttempts() > 0 ? $this->exercise->getAttempts() : get_lang( 'Unlimited' ) ); ?></li>
            <li><?php echo get_lang( 'Anonymous attempts' ) . '&nbsp;: ' . ( $this->exercise->getAnonymousAttempts() == 'ALLOWED' ? get_lang( 'Allowed : do not record usernames in tracking, anonymous users can do the exercise.' ) : get_lang( 'Not allowed : record usernames in tracking, anonymous users cannot do the exercise.' ) ); ?></li>
            <li><?php echo get_lang( 'Show answers' ) . '&nbsp;: '; 
                    switch( $this->exercise->getShowAnswers() ) :
                        case 'ALWAYS' : echo get_lang( 'Yes' ); break;
                        case 'LASTTRY' : echo get_lang( 'After last allowed attempt' ); break;
                        case 'NEVER' : echo get_lang( 'Never' ); break;
                    endswitch;
                ?>
            </li>
            <li><?php echo get_lang( 'Quiz end message' ) . '&nbsp;: ';?>
                <blockquote><?php echo claro_parse_user_text( $this->exercise->getQuizEndMessage() ); ?></blockquote>
            </li>
        </ul>
    </div>
</div>
<br/>
