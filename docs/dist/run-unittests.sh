#!/bin/bash

declare -a failed
# shellcheck disable=SC2013
for testable in $(grep -rl "\$unittest.__FILE__." .);do
    [[ "$testable" == *.php ]] || continue;
    printf "\n#\n# Testing %s\n#\n" "$testable"
    php "$testable";
    (( $? > 0 )) && failed+=("$testable")
done
(( ${#failed[@]} > 0 )) && printf "\n#!!!\n#!!! Failed %d tests: %s\n#!!!\n" "${#failed[@]}" "${failed[*]}"
exit "${#failed[@]}"
