#!/usr/bin/env perl

use strict;
use warnings;

=head1 NAME

check-signed-off.pl - Make sure the last n commits had a Signed-off-by in their commit

=head1 SYNOPSIS

    check-signed-off.pl [n]

=head1 DESCRIPTION

Retrieves the name of the person each of the last n (default: 30) commits was  C<Signed-off-by>.

Prints a Markdown styled list of contributors.

=head1 EXIT STATUS

Returns B<2> if any of the last n commits had no Signed-off-by and lists them to stdout.

=head1 AUTHOR

Konstantin Baierer - L<http://github.com/kba>

=head1 LICENSE AND COPYRIGHT

(c) 2016 Mannheim University Library.

Released under the MIT license.

=cut

# Checks last n commits for Signed-off-by
# Prints a list of contributors
# Dies with a list of not signed-off commit hashes
my $n = $ARGV[0] || 30;

## no critic (ProhibitBacktickOperators)
my $gitlog = qx(git log -z --format=format:"%H%x09%B");
## use critic

my @logentries;
for(split("\x00", $gitlog, $n)) {
    my $entry = { hash => substr($_, 0, 40) };
    my $msg = substr($_, 40);
    ($entry->{signedoff}) = $msg =~ /^Signed-off-by:\s(.*?)\s*</mx;
    push @logentries, $entry;
}

my @signed = grep { defined $_->{signedoff} } @logentries;
## no critic (ProhibitVoidMap)
my %contributors; map { $contributors{$_->{signedoff}}++ } @signed;
## use critic
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
