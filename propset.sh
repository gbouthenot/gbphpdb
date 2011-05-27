#!/bin/sh

svn propset "svn:eol-style" "LF" $*
svn propset "svn:keywords" "Id Revision" $*
