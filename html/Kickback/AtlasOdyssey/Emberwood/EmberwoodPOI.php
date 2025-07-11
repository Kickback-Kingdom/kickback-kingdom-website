<?php

declare(strict_types=1);

namespace Kickback\AtlasOdyssey\Emberwood;

enum EmberwoodPOI: string
{
    case KICKBACK_KINGDOM_STATION_START = "Kickback Kingdom Station (Start)";
    case EN_ROUTE_TO_NEBI = "En route to Nebi";
    case ORBITING_NEBI = "Orbiting Nebi";
    case EN_ROUTE_TO_FELUCCA = "En route to Felucca";
    case FELUCCA_OUTPOST = "Docked at Felucca Outpost";
    case EN_ROUTE_TO_MAGYARION = "En route to Magyarion";
    case ORBITING_MAGYARION = "Orbiting Magyarion";
    case EN_ROUTE_TO_EMBERWOOD = "En route to Emberwood";
    case ORBITING_FELUCCA_MOON_EMBERWOOD = "Orbiting Emberwood, Moon of Felucca";
    case EN_ROUTE_TO_OSTRINUS = "En route to Ostrinus";
    case ORBITING_OSTRINUS = "Returning to Ostrinus";
    case EN_ROUTE_TO_NEPTUNA = "En route to Neptuna";
    case OBITUS_STATION = "Docked at Obitus Station";
    case KICKBACK_KINGDOM_STATION_END = "Kickback Kingdom Station (End)";

    public function getStatuses(): array
    {
        return match($this) {
            self::KICKBACK_KINGDOM_STATION_START => [
                ["status" => "Loading Supplies for Departure", "type" => "normal"],
                ["status" => "Finalizing Cargo Manifest", "type" => "normal"],
                ["status" => "Undergoing Pre-Departure Security Checks", "type" => "normal"],
                ["status" => "Upgrading Defense Systems", "type" => "combat"],
                ["status" => "Running Departure Safety Drills", "type" => "normal"],
                ["status" => "Crew R&R at Station Facilities", "type" => "normal"],
                ["status" => "Testing Long-Range Scanners", "type" => "science"],
                ["status" => "Holding Departure Ceremony", "type" => "normal"],
                ["status" => "Calibrating Medical Systems", "type" => "science"],
                ["status" => "Reinforcing Atlas Wind Sails", "type" => "normal"],
                ["status" => "Synchronizing Navigation with Atlas Star Map", "type" => "science"],
            ],
            self::EN_ROUTE_TO_NEBI, self::EN_ROUTE_TO_FELUCCA, self::EN_ROUTE_TO_MAGYARION, 
            self::EN_ROUTE_TO_EMBERWOOD, self::EN_ROUTE_TO_OSTRINUS, self::EN_ROUTE_TO_NEPTUNA => [
                ["status" => "Monitoring System Diagnostics", "type" => "normal"],
                ["status" => "Navigating through Dense Atlas Winds", "type" => "normal"],
                ["status" => "Adjusting Trajectory to Avoid Asteroids", "type" => "normal"],
                ["status" => "Engaged in Skirmish with Rogue Ships", "type" => "combat"],
                ["status" => "Scanning for Hostile Vessels", "type" => "combat"],
                ["status" => "Engaging in Defensive Maneuvers", "type" => "combat"],
                ["status" => "Fending Off Pirate Interference", "type" => "combat"],
                ["status" => "Running Evasion Drills for Safety", "type" => "combat"],
                ["status" => "All Clear - Systems Nominal", "type" => "normal"],
                ["status" => "Tuning Sails for Atlian Wind Currents", "type" => "normal"],
                ["status" => "Detecting Turbulence in Atlas' Stellar Currents", "type" => "normal"],
                ["status" => "Shielding Against Atlas Star Flares", "type" => "combat"],
                ["status" => "Riding High-Speed Atlian Gusts", "type" => "normal"],
                ["status" => "Tracking Electromagnetic Storm Fronts", "type" => "science"],
                ["status" => "Adjusting for Magnetic Field Shifts", "type" => "normal"],
                ["status" => "Avoiding Plasma Showers from Nebula Fragments", "type" => "normal"],
                ["status" => "Testing Structural Integrity Under Wind Pressure", "type" => "science"],
                ["status" => "Initiating Tactical Maneuvers Against Pursuers", "type" => "combat"],
                ["status" => "Preparing for High-Velocity Currents", "type" => "normal"],
                ["status" => "Engaging Emergency Protocols for Atlas Gusts", "type" => "normal"],
                ["status" => "Reinforcing Shields for Atlian Particle Storm", "type" => "combat"],
                ["status" => "Deploying Scout Drones to Survey Route Ahead", "type" => "science"],
                ["status" => "Engaging Atlas-Bound Thrusters to Maintain Course", "type" => "normal"],
                ["status" => "Deploying Countermeasures Against Hostile Drones", "type" => "combat"],
                ["status" => "Surveying Magnetic Rifts on Path", "type" => "science"],
                ["status" => "Recalibrating Atlian Wind Sails", "type" => "normal"],
                ["status" => "Tracking Incoming Hostile Transmission", "type" => "combat"],
                ["status" => "Running System Reboot Amid Signal Interference", "type" => "normal"],
                ["status" => "Boosting Comms to Counter Atlas' Stellar Noise", "type" => "science"],
                ["status" => "Tethering Ship to Drift Safely in Dense Currents", "type" => "normal"],
                ["status" => "Reading Navigation Anomalies in Atlas Jetstream", "type" => "science"],
                ["status" => "Prepping Combat Drones for Oncoming Threats", "type" => "combat"],
                ["status" => "Cycling Power to Shields Amid High Atlian Gusts", "type" => "combat"],
            ],
            self::ORBITING_NEBI => [
                ["status" => "Monitoring Atmospheric Conditions", "type" => "science"],
                ["status" => "Defending Against Local Raiders", "type" => "combat"],
                ["status" => "Calibrating Sensors for Orbital Scans", "type" => "science"],
                ["status" => "Performing Evasive Maneuvers to Avoid Hostiles", "type" => "combat"],
                ["status" => "Running Emergency Combat Drills", "type" => "combat"],
                ["status" => "Reinforcing Shielding for Potential Combat", "type" => "combat"],
                ["status" => "Monitoring Volcanic Activity on Nebi", "type" => "science"],
                ["status" => "Recording Magnetic Field Data", "type" => "science"],
                ["status" => "Collecting Soil and Rock Samples", "type" => "science"],
                ["status" => "Mapping Local Space Traffic", "type" => "science"],
                ["status" => "Scanning for Rare Gas Elements", "type" => "science"],
                ["status" => "Deploying Atmospheric Observation Drones", "type" => "science"],
                ["status" => "Initiating Geological Surveys", "type" => "science"],
                ["status" => "Stabilizing for Orbital Decay Prevention", "type" => "normal"],
            ],
            self::FELUCCA_OUTPOST => [
                ["status" => "Docked for Trade and Resupply", "type" => "normal"],
                ["status" => "Clearing Outpost Security Protocols", "type" => "normal"],
                ["status" => "Assisting Outpost Defense Drills", "type" => "combat"],
                ["status" => "Responding to Outpost Security Alert", "type" => "combat"],
                ["status" => "Refueling and Preparing Defense Systems", "type" => "combat"],
                ["status" => "Receiving Local Intelligence Update", "type" => "normal"],
                ["status" => "Assisting with Outpost Pirate Threat", "type" => "combat"],
                ["status" => "Reviewing Trade Route Efficiency", "type" => "normal"],
                ["status" => "Recalibrating Planetary Scanners", "type" => "science"],
                ["status" => "Loading Rare Minerals for Transport", "type" => "normal"],
                ["status" => "Processing Diplomatic Communications", "type" => "normal"],
                ["status" => "Scheduling Crew Shore Leave", "type" => "normal"],
                ["status" => "Exchanging Cultural Artifacts with Outpost", "type" => "normal"],
                ["status" => "Repairing Hull Microfractures", "type" => "normal"],
            ],
            
            self::ORBITING_MAGYARION => [
                ["status" => "Orbiting Magyarion for Geological Survey", "type" => "science"],
                ["status" => "Monitoring Activity on Moon Kicsi", "type" => "science"],
                ["status" => "Fending Off Unknown Hostile Ship", "type" => "combat"],
                ["status" => "Testing Orbital Weaponry", "type" => "combat"],
                ["status" => "Running Contingency Plan for Emergencies", "type" => "normal"],
                ["status" => "Preparing for Potential Inbound Threat", "type" => "combat"],
                ["status" => "Reinforcing Shields Against Interference", "type" => "combat"],
                ["status" => "Launching Magnetic Survey Probes", "type" => "science"],
                ["status" => "Surveying Rare Geological Formations", "type" => "science"],
                ["status" => "Mapping Seismic Activity of Kicsi", "type" => "science"],
                ["status" => "Observing High-Altitude Gas Anomalies", "type" => "science"],
                ["status" => "Testing Communication with Research Stations", "type" => "science"],
                ["status" => "Running Deep-Scan Geological Analysis", "type" => "science"],
                ["status" => "Monitoring Stellar Radiation Levels", "type" => "science"],
            ],
            
            self::ORBITING_FELUCCA_MOON_EMBERWOOD => [
                ["status" => "Surveying Moon Emberwood", "type" => "science"],
                ["status" => "Avoiding Hostile Activity Near Felucca's Moon", "type" => "combat"],
                ["status" => "Maintaining Defensive Perimeter", "type" => "combat"],
                ["status" => "Scanning for Pirate Hideouts", "type" => "combat"],
                ["status" => "Engaging Defensive Drones for Patrol", "type" => "combat"],
                ["status" => "Monitoring High-Risk Territory", "type" => "combat"],
                ["status" => "Observing Unique Flora on Emberwood", "type" => "science"],
                ["status" => "Deploying Resource Extraction Drones", "type" => "science"],
                ["status" => "Collecting Soil Samples for Biodiversity Study", "type" => "science"],
                ["status" => "Cataloging Ancient Ruins", "type" => "science"],
                ["status" => "Recording Local Wildlife Sightings", "type" => "science"],
                ["status" => "Mapping Temperature Variance Patterns", "type" => "science"],
                ["status" => "Coordinating with Local Scientists", "type" => "science"],
            ],
            
            self::ORBITING_OSTRINUS => [
                ["status" => "Adjusting for Gravitational Variances", "type" => "normal"],
                ["status" => "Defending Against Hostile Encounter", "type" => "combat"],
                ["status" => "Running Combat Simulations with Crew", "type" => "combat"],
                ["status" => "Coordinating with Ostrinus Defense Forces", "type" => "combat"],
                ["status" => "Preparing for Planetary Descent", "type" => "normal"],
                ["status" => "Securing Cargo in Case of Hostile Interception", "type" => "combat"],
                ["status" => "Conducting Experiments on Purple Flora", "type" => "science"],
                ["status" => "Examining Atmospheric Anomalies", "type" => "science"],
                ["status" => "Calibrating Equipment for High-Gravity Survey", "type" => "science"],
                ["status" => "Communicating with Ground Research Stations", "type" => "science"],
                ["status" => "Organizing Cargo for Drop-Off", "type" => "normal"],
                ["status" => "Transmitting Diplomatic Updates to Kickback HQ", "type" => "normal"],
                ["status" => "Running Diagnostics on Environmental Sensors", "type" => "science"],
            ],
            
            self::OBITUS_STATION => [
                ["status" => "Docked at Medical Station", "type" => "normal"],
                ["status" => "Conducting Anti-Pirate Drills", "type" => "combat"],
                ["status" => "Testing Emergency Docking Ejection System", "type" => "science"],
                ["status" => "Reviewing Combat Preparedness", "type" => "combat"],
                ["status" => "Stocking Anti-Radiation Gear", "type" => "science"],
                ["status" => "Coordinating with Station Security Forces", "type" => "combat"],
                ["status" => "Monitoring for Hostile Activity Near Station", "type" => "combat"],
                ["status" => "Replenishing Medical Supplies", "type" => "normal"],
                ["status" => "Rotating Crew for Medical Exams", "type" => "normal"],
                ["status" => "Consulting with BioNova Medical Experts", "type" => "science"],
                ["status" => "Performing Equipment Maintenance", "type" => "normal"],
                ["status" => "Reviewing Atlas Radiation Reports", "type" => "science"],
                ["status" => "Recalibrating Life Support Systems", "type" => "science"],
                ["status" => "Researching Atlas-Related Ailments", "type" => "science"],
            ],
            
            self::KICKBACK_KINGDOM_STATION_END => [
                ["status" => "Unloading Bi-Weekly Shipment", "type" => "normal"],
                ["status" => "Docking and Refueling for Next Voyage", "type" => "normal"],
                ["status" => "Conducting Final Security Sweep", "type" => "combat"],
                ["status" => "Crew Debriefing on Hostile Encounters", "type" => "combat"],
                ["status" => "Preparing Cargo for Local Distribution", "type" => "normal"],
                ["status" => "Inspecting Ship Systems for Repairs", "type" => "normal"],
                ["status" => "Completing Post-Mission Security Review", "type" => "normal"],
                ["status" => "Scheduling Crew Rotation and Rest", "type" => "normal"],
                ["status" => "Receiving Comms Debrief from Fleet Command", "type" => "normal"],
                ["status" => "Reviewing Navigation Log", "type" => "normal"],
                ["status" => "Updating Atlas Star Map with New Data", "type" => "science"],
                ["status" => "Holding Return Ceremony for Successful Mission", "type" => "normal"],
                ["status" => "Conducting Public Relations with Locals", "type" => "normal"],
                ["status" => "Preparing Ship for Next Scheduled Launch", "type" => "normal"],
            ]
            
        };
    }
}
