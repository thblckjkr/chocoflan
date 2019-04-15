<?php
// Easy Loader
// Custom loader to make more easy the templates view load
class MY_Loader extends CI_Loader {
    // TODO: Extend template and add return arrow on top, here.
    public function template($template_name, $vars = array(), $return = FALSE)
    {
        if( $return ){
            $content  = $this->view('templates/header', $vars, $return);
            $content .= $this->view($template_name, $vars, $return);
            $content .= $this->view('templates/footer', $vars, $return);

            return $content;
        }
        else{
            $this->view('templates/header', $vars);
            $this->view($template_name, $vars);
            $this->view('templates/footer', $vars);
        }
    }
}
