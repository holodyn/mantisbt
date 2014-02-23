<?php

require_once( 'core.php' );
require_once( 'bug_api.php' );

form_security_validate( 'bug_vote_add' );

$f_bug_id   = gpc_get_int( 'bug_id' );
$t_bug      = bug_get( $f_bug_id, true );
$f_username = gpc_get_string( 'username', '' );
$t_logged_in_user_id = auth_get_current_user_id();

if ( is_blank( $f_username ) ) {
  $t_user_id = $t_logged_in_user_id;
} else {
  $t_user_id = user_get_id_by_name( $f_username );
  if ( $t_user_id === false ) {
    $t_user_id = user_get_id_by_realname( $f_username );

    if ( $t_user_id === false ) {
      error_parameters( $f_username );
      trigger_error( ERROR_USER_BY_NAME_NOT_FOUND, E_USER_ERROR );
    }
  }
}

if ( user_is_anonymous( $t_user_id ) ) {
  trigger_error( ERROR_PROTECTED_ACCOUNT, E_USER_ERROR );
}

bug_ensure_exists( $f_bug_id );

if( $t_bug->project_id != helper_get_current_project() ) {
  # in case the current project is not the same project of the bug we are viewing...
  # ... override the current project. This to avoid problems with categories and handlers lists etc.
  $g_project_override = $t_bug->project_id;
}

if ( $t_logged_in_user_id == $t_user_id ) {
  access_ensure_bug_level( config_get( 'vote_bug_threshold' ), $f_bug_id );
} else {
  access_ensure_bug_level( config_get( 'vote_add_others_bug_threshold' ), $f_bug_id );
}

$result = bug_vote( $f_bug_id, $t_user_id );

if( $result ){
  form_security_purge( 'bug_vote_add' );
  // print_successful_redirect_to_bug( $f_bug_id );
  if( $_REQUEST['mode'] == 'quick' ){
    html_page_top( null, $p_redirect_to );
    echo '<script> setTimeout(function(){ history.back(-1); },'.((int)current_user_get_pref('redirect_delay',1)*1000).'); </script>';
    echo '<br /><div class="center">';
    echo 'You vote was recorded.<br />';
    echo '<a href="javascript:history.back(-1);">[ Return to List ]</a> ';
    print_bracket_link( 'view.php?id='.$f_bug_id, lang_get( 'proceed' ) );
    echo '</div>';
    html_page_bottom();
  } else
    print_successful_redirect( string_get_bug_view_url($f_bug_id, auth_get_current_user_id()) );
} else {
  html_page_top( null, $p_redirect_to );
  echo '<br /><div class="center">';
  echo 'You have already voted for this bug.<br />';
  print_bracket_link( 'view.php?id='.$f_bug_id, lang_get( 'proceed' ) );
  echo '</div>';
  html_page_bottom();
}
