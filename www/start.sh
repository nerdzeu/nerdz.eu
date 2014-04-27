#!/bin/sh

HOST="127.0.0.1"
PORT="1337"
ROOTPATH="`dirname \"$0\"`"
ROOTPATH="`readlink -f \"$ROOTPATH/..\"`"

if test "$#" -eq 1 -o "$#" -eq 2; then
  if ! test "x$1" = x; then
    HOST="$1"
  fi

  if ! test "x$2" = x; then
    PORT="$2"
  fi
fi

php -S "$HOST":"$PORT" -t "$ROOTPATH" "$ROOTPATH"/www/routing.php
