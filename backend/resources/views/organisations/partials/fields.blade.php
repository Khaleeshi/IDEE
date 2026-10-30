<x-field name="name" label="Nom de l'organisation" :value="$organisation->name" required maxlength="150" />
<x-select name="kind" label="Type" :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])->all()" :value="$organisation->kind?->value" placeholder="Choisir un type" />
<x-field name="description" type="textarea" rows="4" label="Présentation" :value="$organisation->description" required maxlength="3000"
         hint="Ce que fait l'organisation et ce qu'elle propose aux entrepreneurs." />
<x-field name="region" label="Région (facultatif)" :value="$organisation->region" placeholder="Ex. : Dakar" maxlength="100" />
<x-field name="website" type="url" label="Site internet (facultatif)" :value="$organisation->website" placeholder="https://" maxlength="300" />
