# This file should be sourced, not run

[[ -n $TRACE ]] && [[ $TRACE != 0 ]] && set -x

set -o errexit

ORIG_PWD=$(pwd)
cd $(dirname $0)/..
BASE_DIR=$(pwd)

DATA_DIR=${DATA_DIR:-$HOME/svn-data} # should NOT be under the project root, it freaks IDEA out even if its excluded
ARCHIVE_DIR=${ARCHIVE_DIR:-$DATA_DIR/archive}

TMPDIR=${TMPDIR:-/tmp} # no underscore on this one, it's an old unixism

mkdir -p $DATA_DIR $ARCHIVE_DIR

PLUGINS_REMOTE=${PLUGINS_REMOTE:-https://plugins.svn.wordpress.org}
THEMES_REMOTE=${THEMES_REMOTE:-https://themes.svn.wordpress.org}

YMD=$(date +%Y-%m-%d)

function warn {
    echo "$@" >&2
}

function die() {
    warn "$@"
    exit 1
}

function RUN() {
  [[ -n $DRY_RUN ]] && [[ $DRY_RUN != 0 ]] && _run=echo
  $_run "$@"
}

function enforce_svn_root() {
  [[ -d .svn ]] || die "$(pwd) does not look like a svn checkout -- exiting"
}

function _use_checkout() {
  type=$1
  remote=$2

  cd $DATA_DIR
  mkdir -p svn/$type
  cd svn/$type
  if [[ -d .svn ]]; then
    $BASE_DIR/bin/svn-get-immediates
  else
    depth=${IMMEDIATES_DEPTH:-immediates}
    svn checkout --ignore-externals --depth=$depth $remote
  fi
}

function use_plugins() {
  _use_checkout plugins $PLUGINS_REMOTE
}

function use_themes() {
  _use_checkout themes $THEMES_REMOTE
}