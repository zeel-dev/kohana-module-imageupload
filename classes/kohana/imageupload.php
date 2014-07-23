<?php defined('SYSPATH') OR die('No direct access allowed.');



class Kohana_ImageUpload extends UploadHandler
{


    /**
     * Allows to access response data before encoded it is encoded to javascript
     *
     * @var   mixed   $response   saved response
     */
    public
        $response = false;



    /**
     * Add extra options
     * TODO: Use kohana configuration
     *
     * @param   array     $options          adds extra
     * @param   boolean   $initialize       passed down
     * @param   number    $error_messages   passed down
     */
    function __construct($options = null, $initialize = true, $error_messages = null)
    {

        $_options = array(
            // This options prevents generating new unique file name
            'allow_overwrite' => false,
            'filename'        => false,
            'mkdir_mode'      => 0775, // 0755 + 0664 -> 0765

            'save_response'   => false,
        );

        if ($options)
        {
            $_options = array_merge($_options, $options);
        } # if

        parent::__construct( $_options, $initialize, $error_messages );
    } # fucntion



    /**
     * Implemented filename & allow_overwrite options
     *
     * @param   string   $name            of file
     * @param   string   $type            mime of the file
     * @param   number   $index           passed down
     * @param   number   $content_range   passed down
     * @return  string                    of file name
     */
    protected function get_file_name($name, $type = null, $index = null, $content_range = null)
    {
        if ( $this->options['filename'] )
        {
            // If file name option exit use it
            $name = $this->options['filename'];

            // Add extension to file name
            if ( strpos($name, '.') === false && preg_match( Arr::get( $this->options, 'append_suffix_regex', '/^image\/(gif|jpe?g|png)/' ), $type, $matches))
            {
                $name .= '.'. Arr::get( Arr::get( $this->options, 'transform_suffix', array()), $matches[1], $matches[1] );
            }

            if ( !is_file($this->get_upload_path($name)) || $this->options['allow_overwrite'] )
            {
                return $name;
            } # if
        } # if

        return parent::get_file_name($name, $type, $index, $content_range);
    } # function



    /**
     * Creates or update file on the server.
     * Added ability for file version to be in the same directory
     *
     * @param   string   $file_name   of image
     * @param   array    $version     of options
     * @param   array    $options
     * @return  boolean               true if update or create was successfull
     */
    protected function create_scaled_image( $file_name, $version, $options )
    {
        // Change behaviour of file upload
        if ( !empty( $version ) && isset( $options['filename'] ))
        {
            $return = parent::create_scaled_image( $file_name, $version, $options );

            // Move image if file name is specified
            if ( $return )
            {
                $old_file_path = $this->get_upload_path( null, $version ) . $file_name;

                // Add extension to file name
                if ( strpos( $options['filename'], '.' ) === false )
                {
                    $options['filename'] .= '.' . pathinfo($file_name, PATHINFO_EXTENSION);
                } # if

                $file_name = $options['filename'];

                $new_file_path = $this->get_upload_path( $file_name );

                $return = rename( $old_file_path, $new_file_path );

                @chmod( $new_file_path, 0664 );
            } # if
        }
        else
        {
            $return = parent::create_scaled_image( $file_name, $version, $options );

            @chmod( $this->get_upload_path( $file_name ), 0664 );
        } # else

        return $return;
    } # function



    /**
     * Chages permissions for uploaded file
     *
     * @param   string   $file_path   of image
     * @param   ???      $file        of options
     * @return  void
     */
    protected function handle_image_file( $file_path, $file )
    {
        @chmod( $file_path, 0664 );

        return parent::handle_image_file( $file_path, $file );
    } # function



    /**
     * Make sure delete never happens
     *
     * @param   boolean   $print_response
     * @return  void
     */
    protected function generate_response( $content, $print_response = true ) {
        return parent::generate_response( $content, $print_response );
    } # function



    /**
     * Store response
     *
     * @param   string   $str
     * @return  void
     */
    protected function body( $str )
    {
        if ( is_string( $str ) && strlen( $str ) > 1 && $str[0] == '{' )
        {
            $this->response = json_decode( $str, true );
        } # if

        parent::body( $str );
    } # method



    /**
     * Make sure delete never happens
     *
     * @param   boolean   $print_response
     * @return  void
     */
    public function delete($print_response = true)
    {
        $success = false;
        return $this->generate_response(array('success' => $success), $print_response);
    } # function

} # class
