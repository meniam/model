<?php

namespace Model\Code\Generator;

class DocBlockGenerator extends \Zend\Code\Generator\DocBlockGenerator
{
    /**
     * docCommentize()
     *
     * @param string $content
     * @return string
     */
    protected function docCommentize($content)
    {
        $indent  = $this->getIndentation();
        $output  = $indent . '/**' . self::LINE_FEED;
     //   $content = wordwrap($content, 80, self::LINE_FEED);
        $lines   = explode(self::LINE_FEED, $content);
        foreach ($lines as $line) {
            $output .= $indent . ' *';
            if ($line) {
                $output .= " $line";
            }
            $output .= self::LINE_FEED;
        }
        $output .= $indent . ' */' . self::LINE_FEED;
        return $output;
    }
}
