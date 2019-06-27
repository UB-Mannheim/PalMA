# Analyzing log files


``ls *.log | parallel --progress 'palma_analyze {} > {.}.txt'``

``ls *.log | parallel --progress 'palma_analyze -s {} > {.}.summary.txt'``

``head -n1 `ls -1 *.summary.txt | head -n1` > summary.txt; tail -qn1 *.summary.txt >> summary.txt``
