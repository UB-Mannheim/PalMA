#!/usr/bin/env perl

use strict; use warnings;

# Checks last n commits for Signed-off-by
# Prints a list of contributors
# Dies with a list of not signed-off commit hashes
my $n = $ARGV[0] || 30;

my $gitlog = qx(git log -z --format=format:"%H%x09%B");
my @logentries = map {
        my $entry = {
            hash => substr($_, 0, 40),
        };
        my $msg = substr($_, 40);
        ($entry->{signedoff}) = $msg =~ /^Signed-off-by:\s(.*?)\s*</mx;
        $entry;
    } split("\x00", $gitlog, $n);

my @signed = grep { defined $_->{signedoff} } @logentries;
my %contributors; map { $contributors{$_->{signedoff}}++ } @signed;
printf "## CONTRIBUTORS\n";
for (sort { $contributors{$b} <=> $contributors{$a} } keys %contributors) {
    printf "  * %s (%s)\n", $_, $contributors{$_};
}
if (scalar @signed < scalar @logentries) {
    my @unsigned = grep { ! defined $_->{signedoff} } @logentries;
    printf "These commits are unsigned:\n";
    system "git show -s --format=format:'%C(auto)%h%d %cr [%aN] %s ' " . join ' ', map { $_->{hash} } @unsigned;
    exit 1
}
