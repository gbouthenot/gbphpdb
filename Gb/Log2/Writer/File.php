<?php
/**
 * \Gb\Log2\Writter\File
 *
 * @author Gilles Bouthenot
 */

namespace Gb\Log2\Writer;

class File extends AbstractWriter
{
    protected $stream;

    public function __construct($streamOrUrl)
    {
        // defaults
        $mode = "a";
        $logSeparator = PHP_EOL;
        $format = "{{dateShort} }{{44>remoteAddr} }{{levelString} }{user={remoteUser} }";
        $format .= "{{prepend} }{{text}}{ {append}}";

        if (is_array($streamOrUrl)) {
            if (isset($streamOrUrl["mode"])) {
                $mode = $streamOrUrl["mode"];
            }
            if (isset($streamOrUrl["log_separator"])) {
                $logSeparator = $streamOrUrl["log_separator"];
            }
            if (isset($streamOrUrl["stream"])) {
                $streamOrUrl = $streamOrUrl["stream"];
            }
        }

        if (is_resource($streamOrUrl)) {
            if ('stream' != get_resource_type($streamOrUrl)) {
                throw new \Gb_Exception(sprintf(
                    'Resource is not a stream; received "%s',
                    get_resource_type($streamOrUrl)
                ));
            }
            if ('a' != $mode) {
                throw new \Gb_Exception(sprintf(
                    'Mode must be "a" on existing streams; received "%s"',
                    $mode
                ));
            }
            $this->stream = $streamOrUrl;
        } else {
            $this->stream = fopen($streamOrUrl, $mode, false);
            if (!$this->stream) {
                throw new \Gb_Exception(sprintf(
                    '"%s" cannot be opened with mode "%s"',
                    $streamOrUrl,
                    $mode
                ), 0, $error);
            }
        }

        $this->logSeparator($logSeparator);
        $this->format($format);
    }

    public function writeV1(array $logdata)
    {
        //echo "level:$level string:$string\n";
        //print_r($logdata);
        //$line = "ABC" . $this->logSeparator;
        //fwrite($this->stream, $line);

        $format = "aa{{dateShort}} {{remoteAddr}} {{44}} {{levelString}} {{prepend }}";
        $format .= "{{text}}{{ append}} user={{remoteUser}}bb";

        $logdata["append"]="append";
        $logdata["prepend"]="prepend";

        $matches = null;
        $pattern = "/\{\{(\s*.+?\s*)\}\}/";
        $nbMatches = preg_match_all($pattern, $format, $matches);
        print_r($matches);

        echo $format."\n";
        for ($i = 0; $i < $nbMatches; $i++) {
            $subsearch = $matches[0][$i];
            $subkey = $matches[1][$i];
            $keyword = trim($subkey);
            $subst = isset($logdata[$keyword]) ? str_replace($keyword, $logdata[$keyword], $subkey) : "";
            $format = str_replace($subsearch, $subst, $format);
            echo $format . $this->logSeparator;
        };
    }


    public function write(array $logdata)
    {
        //$format = "{{dateShort} }{X{notexist}X}{{remoteAddr} }{{44;}}{{levelString} }{user={8<remoteUser} }";
        //$format .= "{user={40=remoteUser} }{{prepend} }{{text}} {{append}}";

        $format = $this->format();

        $logdata["append"] = "append";
        $logdata["prepend"] = "prepend";
        $logdata["remoteUser"] = "12345678901234567890";

        $output = \Gb\Log2::formatString($logdata, $format);
        $output .= $this->logSeparator;

        fwrite($this->stream, $output);
    }


    /**
     * Close the stream resource.
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
