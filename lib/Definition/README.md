# Entity Definitions

Entities consist of pre-defined and user-defined properties. Entities are tied to application logic by specifying what they actually represent (`model`) and the responsible `interpreter`. While volkszaehler always stores raw data readings, Interpreters know how to convert raw data to meaningful, presentation-ready data.

## Channel Properties

### unit

`Unit` is the physical unit of the underlying measurable.

### resolution

`Resolution` defines how many readings constitute 1 unit. If e.g. a gas meter has 100 impulses per m3, and unit is m3, resolution needs to be 100. Resolution allows the interpreter to convert raw readings to actual values according to unit.

### scale

Typically, resolution defines how many impulses/readings make up one unit. However, for certain physical properties like e.g. electrical energy, it is custom to use SI-derived units instead of the base units. For electrical energy that means that `kWh` is the common unit for measurement opposed to `Wh`.
`Scale` defines what scale is applied between base unit to actual unit. Scale applies to

  - resolution
  - cost
  - initialconsumption

### hasConsumption

Is `true` if a physical entity supports integration over time.

### initialconsumption

Initial consumption can be set to define how much consumption a channel has accumulated before measurement in volkszaehler has started. For channels using `AccumulatorInterpreter` hat value is actually stored in the database, but not easily accessible by the middleware. Using `initialconsumption` instead any arbitrary value can be used as starting point. Unit for initialconsumption is the base unit (scaled according to `scale`) converted to an hourly consumption.

## Examples

### Electrical Meter

````javascript
	{
		"name" : "electric meter",
		"required"		: ["resolution"],
		"optional" : ["tolerance", "cost", "local", "initialconsumption"],
		"unit"			: "W",
		"scale"			: 1000,
		"hasConsumption"	: true,
		"interpreter"		: "Volkszaehler\\Interpreter\\AccumulatorInterpreter",
		...
	}
````

Defines a channel type of `electric meter` that is measured in W. Unit `scale` is 1000, that is kW. As it uses `AccumulatorInterpreter`, the readings are in impulses/ticks/counts per consumption unit:

  - `resolution`: impulses per kWh (accoding to `scale`)
  - `cost`: cost in € per per kWh (again accoding to `scale`)
  - `initialconsumption`: first counter reading in kWh

### Gas Meter

````javascript
	{
		"name"			: "gas",
		"required"		: ["resolution"],
		"optional"		: ["tolerance", "cost", "local", "initialconsumption"],
		"unit"			: "m³/h",
		"interpreter"		: "Volkszaehler\\Interpreter\\ImpulseInterpreter",
		"hasConsumption"	: true,
````

Defines a channel type of `gas` that is measured in m³/h. Unit `scale` is not defined and therefore defaults to 1. As it uses `ImpulseInterpreter`, the readings are consumption values:

  - `resolution`: impulses per m³
  - `cost`: cost in € per per m³
  - `initialconsumption`: first counter reading in m³
