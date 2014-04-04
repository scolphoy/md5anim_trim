#!/bin/bash
# Script for reducing accuracy for floating point numbers.

# ----------------------------------------------------------------------------
# "THE BEER-WARE LICENSE" (Revision 42):
# <juhani.toivonen@cs.helsinki.fi> wrote this file. As long as you retain this 
# notice you can do whatever you want with this stuff. If we meet some day, 
# and you think this stuff is worth it, you can buy me a beer in return. 
# 
# - Juhani Toivonen
# ----------------------------------------------------------------------------
 
 
# How many decimal places do you want
scale=6

# Main script
mkdir -p -v trimmed;
for file in $*; do
  cat $file |sed -e "s/\b\([-,0-9]*\.[0-9]\{$scale\}\)[0-9]*\b/\1/g" > trimmed/$(basename $file)
  echo "$file -> trimmed/$(basename $file)"
done
