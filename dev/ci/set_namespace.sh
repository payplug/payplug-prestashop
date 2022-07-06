#!/bin/sh

echo "Set namespace";

mode="normal"
namespace="PayPlug"
newnamespace="PayLater"
class="Payplug"
newclass="PsPayLater"
# Process all options supplied on the command line
while getopts m: flag;
do
    case "${flag}" in
        m) mode=${OPTARG};;
        *) eval echo "Unrecognized arg \$${OPTARG}"; usage; exit ;;
    esac
done

if  [ "$mode" = "reverse" ]; then
  echo "Current mode is ${mode}, convert namespace back to Payplug"
  namespace="PayLater"
  newnamespace="PayPlug"
  class="PsPayLater"
  newclass="Payplug"
else
  if [ "$mode" = "normal" ]; then
  echo "Default mode (${mode}), convert namespace to Paylater"
  fi
fi

#grep -rl `basename PayPlug` . ':!dev/ci/set_namespace.sh' | xargs sed -i -e 's/'`basename PayPlug`'/'`basename PayPlug`'/g';
git grep -rl use `basename ${namespace}` . ':!dev/**' ':!*AdminPsPayLaterController.php' | xargs sed -i -e 's/use '`basename ${namespace}`'/use '`basename ${newnamespace}`'/g';

#rename the front controllers files
git grep -rl 'class '${class} ./controllers/front | xargs sed -i -e 's/class '${class}'/class '${newclass}'/g';

echo "Remove temporay file";
rm ./*.php-e ./*/*.php-e ./*/*/*.php-e ./*/*/*/*.php-e;

echo "End";