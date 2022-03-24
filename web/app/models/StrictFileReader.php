<?php

class StrictFileReader {
    private $f;
    private $buf = '', $off = 0;

    public function __construct($file_name) {
        $this->f = fopen($file_name, 'r');
    }

    public function failed() {
        return $this->f === false;
    }

    public function readChar() {
        if (isset($this->buf[$this->off])) {
            return $this->buf[$this->off++];
        }
        return fgetc($this->f);
    }
    public function unreadChar($c) {
        $this->buf .= $c;
        if ($this->off > 1000) {
            $this->buf = substr($this->buf, $this->off);
            $this->off = 0;
        }
    }

    public function readString() {
        $str = '';
        while (true) {
            $c = $this->readChar();
            if ($c === false) {
                break;
            } elseif ($c === " " || $c === "\n" || $c === "\r") {
                $this->unreadChar($c);
                break;
            } else {
                $str .= $c;
            }
        }
        return $str;
    }
    public function ignoreWhite() {
        while (true) {
            $c = $this->readChar();
            if ($c === false) {
                break;
            } elseif ($c === " " || $c === "\n" || $c === "\r") {
                continue;
            } else {
                $this->unreadChar($c);
                break;
            }
        }
    }

    public function eof() {
        return feof($this->f);
    }

    public function close() {
        fclose($this->f);
    }
}