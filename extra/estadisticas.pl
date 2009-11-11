#!/usr/bin/perl
#
# Ejemplo de uso para extraer estadísticas de octubre de 2009 
#
#  cat log-2009-10-*.php | ./estadisticas.pl

my $tamtotal = 0;
my $fichtotal = 0;

while ($l = <STDIN>) {
	if ($l =~ /^UPLOAD/) {
		chomp($l);
		my @tr = split(/ /, $l);
		my $parcial = $tr[@tr-1];

		$tamtotal += $parcial;
		$fichtotal++;
	}

}

print $fichtotal . " ficheros, " . &tam_legible($tamtotal) . "\n";


sub tam_legible {
	my @tam = ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	my $cantidad = shift;
	my $i = 0;
	my $bytesi = 1;

	for(;$cantidad > 1024*$bytesi; $i++) {
		$bytesi *= 1024;
	}

	my $cadena = sprintf "%.2f", $cantidad/$bytesi;
 	$cadena .= " " . $tam[$i]
}

