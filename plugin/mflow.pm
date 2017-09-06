################################################################
#
# mflow.pm [MFLOW]
# Author: masuwonchon@gmail.com
#
################################################################

package mflow;

use strict;
use warnings;

use mflow::RPC;
use mflow::Utils;

our $VERSION = 136;
our $MFLOW_VERSION = "1.0.0";
our $nfdump_version;

use Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(
    nfdump_version
);

our %cmd_lookup = (
    'get_backend_version'       => \&get_backend_version,
    'get_nfdump_version'        => \&get_nfdump_version,
    'get_nfsen_profiledatadir'  => \&get_nfsen_profiledatadir
);

sub Init {
    # check nfdump version
    $nfdump_version = nfdump_version_check();
    log_info("Detected nfdump v".$nfdump_version);
    
    return 1;
}

sub run {
    my $argref       = shift;
    my $profile      = $$argref{'profile'};
    my $profilegroup = $$argref{'profilegroup'};
    my $timeslot     = $$argref{'timeslot'};
}

sub Cleanup {
    log_info("Cleanup finished");
}

1;
