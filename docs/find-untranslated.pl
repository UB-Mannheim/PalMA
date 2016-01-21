#!env perl
use strict; use warnings;
use Data::Dumper;
use Term::ANSIColor;

my $LOCALEDIR=$ENV{LOCALEDIR} || 'locale';
my %flags = map {($_=>1)} grep { /^-/ } @ARGV;
if ($flags{'-h'} || $flags{'--help'}) {
    printf "Usage: $0 [--color] [--missing] [locales...]\n";
    exit 0;
}
my @locales = grep { ! /^-/ } @ARGV;
unless (scalar @locales) {
    @locales = split('\n', qx(ls $LOCALEDIR|sed 's/\\..*//'));
}

sub parse_po {
    my $fname = sprintf "%s/%s.UTF-8/LC_MESSAGES/palma.po", $LOCALEDIR, $_[0];
    my @lines = do {
        open my $fh, '<', $fname or die ("No such file $fname");
        <$fh>;
    };
    my ($cur, %ret);
    my $state_msgid = 1;
    for (@lines) {
        if (/^msgid "(.*)"/) {
            $state_msgid = 1;
            $cur = $1;
            $ret{$cur} = "";
        } elsif (/^msgstr "(.*)"/) {
            $state_msgid = 0;
            $ret{$cur} .= $1;
        } elsif (/^"(.*)"/) {
            if ($state_msgid) {
                $cur .= $1;
            } else {
                $ret{$cur} .= $1;
            }
        }
    }
    delete $ret{""};
    return %ret;
}
sub percent {
    my $perc = sprintf("%.2f", 100 * $_[0]);
    return $perc unless $flags{'--color'};
    return colored($perc,
        $perc == 100 ? 'bold green' :
        $perc > 90 ? 'green' :
        $perc > 50 ? 'yellow' :
        'red');
}

my %reference_po = parse_po('en_US');
for my $locale (sort @locales) {
    next if $locale eq 'en_US';
    my %po = parse_po($locale);
    my @total = keys %po;
    my @untranslated = grep { $po{$_} =~ /^$/ || exists $reference_po{$po{$_}} } @total;
    printf("%s\t%s%%\t (%s / %s)\n",
        $locale,
        percent((scalar @total - scalar @untranslated) / scalar @total),
        scalar @untranslated,
        scalar @total);
    if ($flags{'--missing'}) {
        for (sort @untranslated) {
            printf("  * %s\n", $_)
        }
    }
}
# warn Dumper keys %reference_po;
