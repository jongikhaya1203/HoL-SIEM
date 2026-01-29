# Emergency Shutdown (ESD) System - Complete Guide

## Overview

The Emergency Shutdown (ESD) System is a comprehensive, automated plant shutdown and startup management system built following international industry standards:

- **IEC 61511** - Functional Safety for Process Industry
- **ISA-84** - Safety Instrumented Systems
- **API RP 14C** - Recommended Practice for Analysis, Design, Installation, and Testing of Safety Systems

## Key Features

### ✅ Multi-Level Shutdown Classification
- **Level 0 (L0)** - Normal Operation
- **Level 1 (L1)** - Process Shutdown (partial)
- **Level 2 (L2)** - Unit Shutdown (complete unit)
- **Level 3 (L3)** - Plant Shutdown (full plant)
- **ESD1-3** - Emergency Shutdown Levels

### ✅ Automated Sequence Execution
- Step-by-step automated procedures
- Parallel and sequential step execution
- Hold points for operator confirmation
- Configurable timeouts
- Automatic failure detection

### ✅ Safety Interlocks
- Pre-configured safety interlocks
- Real-time condition monitoring
- Automatic trigger on violation
- Bypass capability (with authorization)
- Priority-based execution

### ✅ Startup Permissives
- Pre-start condition verification
- Equipment readiness checks
- Safety system verification
- Gradual startup procedures

### ✅ Comprehensive Logging
- Real-time execution logs
- Audit trail for compliance
- Error tracking
- Performance metrics

### ✅ Operator Interface
- User-friendly HMI
- Real-time status monitoring
- Approval workflow
- Emergency override capability

---

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                 Shutdown Manager HMI                     │
│  - Initiate shutdowns/startups                          │
│  - Approve sequences                                     │
│  - Monitor execution                                     │
│  - View logs and history                                │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│              ShutdownManager Class (PHP)                 │
│  - Sequence orchestration                               │
│  - Interlock evaluation                                  │
│  - Permissive checking                                   │
│  - Step execution                                        │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                 ValveController                          │
│  - Valve operations                                      │
│  - Pump control                                          │
│  - Equipment shutdown                                    │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                    PLC/DCS Layer                         │
│  - Physical equipment control                            │
│  - Sensor readings                                       │
│  - Actuator commands                                     │
└─────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Core Tables

#### 1. `shutdown_levels`
Defines severity levels for shutdowns
- L0 to L3, ESD1 to ESD3
- Severity ratings
- Auto-trigger settings

#### 2. `shutdown_sequences`
Automated shutdown/startup procedures
- Sequence name and type
- Estimated duration
- Approval requirements
- Active/inactive status

#### 3. `shutdown_sequence_steps`
Individual steps in each sequence
- Step number and description
- Action type (close_valve, stop_pump, etc.)
- Target assets/tags
- Timeouts and hold points
- Parallel execution groups

#### 4. `shutdown_interlocks`
Safety interlock definitions
- Condition logic
- Trigger actions
- Priority levels
- Bypass settings

#### 5. `shutdown_interlock_conditions`
Tag-based conditions for interlocks
- Tag monitoring
- Comparison operators
- Setpoints and ranges
- Logic (AND/OR)

#### 6. `shutdown_permissives`
Startup preconditions
- Required tag values
- Equipment states
- Tolerance settings

#### 7. `shutdown_executions`
Tracks all shutdown/startup executions
- Status tracking
- Approval workflow
- Timestamps
- Current step

#### 8. `shutdown_execution_logs`
Detailed execution logs
- Timestamped events
- Log levels (INFO, WARNING, ERROR)
- Tag values
- Step results

#### 9. `well_shutdown_procedures`
Well-specific shutdown procedures
- Choke close rates
- Depressurization parameters
- Valve isolation sequences

#### 10. `dcs_interface_commands`
DCS/PLC command tracking
- Command types
- Confirmation status
- Retry logic

---

## Installation Steps

### Step 1: Create Database Tables

```bash
http://localhost/networkscanscada/create_shutdown_tables.php
```

This creates all ESD system tables and inserts default shutdown levels.

### Step 2: Create Sample Sequences

```bash
http://localhost/networkscanscada/create_shutdown_sequences.php
```

Creates three demonstration sequences:
1. **ESD3 Emergency Shutdown** (5 steps, ~3 minutes)
2. **L1 Normal Shutdown** (9 steps, ~10 minutes)
3. **Normal Startup** (15 steps, ~15 minutes)

Also creates:
- 2 Safety interlocks
- 2 Startup permissives

### Step 3: Access Shutdown Manager

```bash
http://localhost/networkscanscada/shutdown_manager.php
```

---

## Creating Custom Sequences

### Sequence Configuration

```sql
INSERT INTO shutdown_sequences
(site_id, sequence_name, sequence_type, shutdown_level_id,
 description, estimated_duration_seconds, requires_operator_approval)
VALUES
(1, 'Well Shutdown - Routine', 'shutdown', 2,
 'Routine well shutdown procedure', 300, 1);
```

### Adding Steps

```sql
INSERT INTO shutdown_sequence_steps
(sequence_id, step_number, step_name, step_description,
 action_type, target_asset_id, timeout_seconds)
VALUES
(1, 1, 'Close Master Valve', 'Close well master valve',
 'close_valve', 5, 30);
```

### Action Types

| Action Type | Description | Parameters |
|------------|-------------|------------|
| `close_valve` | Close a valve | `{"position": 0}` |
| `open_valve` | Open a valve | `{"position": 100}` |
| `stop_pump` | Stop a pump | `{}` |
| `start_pump` | Start a pump | `{"speed": 1800}` |
| `shutdown_well` | Well shutdown | `{"rate": 10}` |
| `depressurize` | Depressurize equipment | `{"target_pressure": 0, "rate": 1.0}` |
| `wait` | Wait/delay | `{"duration": 60}` |
| `check_condition` | Verify condition | `{"tag": "...", "condition": ">", "value": 0}` |
| `alarm` | Raise alarm | `{"message": "...", "priority": "high"}` |

---

## Safety Interlocks

### Creating an Interlock

```sql
-- 1. Create interlock
INSERT INTO shutdown_interlocks
(interlock_name, description, site_id, interlock_type,
 trigger_action, shutdown_sequence_id, priority)
VALUES
('High Pressure Trip', 'Triggers shutdown on high pressure',
 1, 'safety', 'full_shutdown', 2, 1);

-- 2. Add condition
INSERT INTO shutdown_interlock_conditions
(interlock_id, tag_id, condition_operator, setpoint_value)
VALUES
(1, 5, '>', 150.0);
```

### Interlock Types

- **safety** - Critical safety interlocks
- **process** - Process protection
- **equipment** - Equipment protection
- **environmental** - Environmental protection

### Trigger Actions

- **alarm** - Raise alarm only
- **partial_shutdown** - Shutdown specific unit
- **full_shutdown** - Complete plant shutdown
- **prevent_startup** - Block startup until resolved

---

## Startup Permissives

### Creating Permissives

```sql
INSERT INTO shutdown_permissives
(permissive_name, description, sequence_id, step_id,
 tag_id, required_value, tolerance)
VALUES
('Minimum Tank Level', 'Tank must have minimum level',
 3, 7, 12, 2.0, 0.5);
```

Permissives ensure:
- Equipment is ready
- Pressures are adequate
- Temperatures are correct
- Valves are in correct position
- No safety alarms active

---

## Usage Examples

### Example 1: Initiate Normal Shutdown

1. Open Shutdown Manager
2. Click on "Normal Process Shutdown" sequence
3. Enter reason: "Planned maintenance"
4. Click "Initiate Shutdown"
5. Supervisor approves the shutdown
6. System executes steps automatically
7. Operator confirms at hold points

### Example 2: Emergency Shutdown

1. Open Shutdown Manager
2. Click on "Emergency Shutdown - Total Plant"
3. Enter reason: "Gas leak detected"
4. Check "This is an EMERGENCY"
5. Click "Initiate Shutdown"
6. Executes immediately without approval
7. All safety actions completed in ~3 minutes

### Example 3: Startup After Shutdown

1. Verify all safety systems operational
2. Click on "Normal Process Startup"
3. Enter reason: "Resuming after maintenance"
4. System checks all startup permissives
5. If permissives satisfied, begins startup
6. Gradual pressurization and equipment start
7. Operator confirms at critical hold points
8. System reaches normal operation

---

## Sequence Execution Flow

```
┌─────────────────────────┐
│ Operator Initiates      │
│ Shutdown Sequence       │
└──────────┬──────────────┘
           │
           ↓
┌─────────────────────────┐
│ Check Interlocks        │
│ (if not emergency)      │
└──────────┬──────────────┘
           │
           ↓
┌─────────────────────────┐
│ Require Approval?       │
│ (if not emergency)      │
└──────────┬──────────────┘
           │
           ↓
┌─────────────────────────┐
│ Execute Step 1          │
│ - Check permissives     │
│ - Perform action        │
│ - Verify completion     │
└──────────┬──────────────┘
           │
           ↓
┌─────────────────────────┐
│ Hold Point?             │
│ (wait for confirmation) │
└──────────┬──────────────┘
           │
           ↓
┌─────────────────────────┐
│ Execute Step 2          │
└──────────┬──────────────┘
           │
           ⋮
           │
           ↓
┌─────────────────────────┐
│ All Steps Complete      │
│ Mark as COMPLETED       │
└─────────────────────────┘
```

---

## Hold Points

### When to Use Hold Points

1. **Critical Safety Verification**
   - Before starting major equipment
   - After depressurization
   - Before opening high-pressure lines

2. **Equipment Inspection**
   - Visual inspection required
   - Manual valve verification
   - Leak checks

3. **Process Stability**
   - Allow time for stabilization
   - Verify parameters in range
   - Check for abnormal conditions

### Configuring Hold Points

```sql
UPDATE shutdown_sequence_steps
SET hold_point = TRUE,
    requires_confirmation = TRUE
WHERE step_number = 5;
```

---

## Parallel Execution

Execute multiple steps simultaneously:

```sql
-- Steps with same parallel_group execute together
UPDATE shutdown_sequence_steps
SET parallel_group = 1
WHERE step_number IN (3, 4, 5);
```

Example: Close multiple isolation valves simultaneously

---

## Error Handling

### Step Failure

If a step fails:
1. Execution pauses
2. Error logged
3. Alarm raised
4. Operator notified

Options:
- Retry step
- Skip step (with authorization)
- Abort sequence

### Timeout Handling

Each step has a timeout:
```sql
timeout_seconds = 30  -- Fail if not complete in 30s
```

On timeout:
- Log warning
- Attempt recovery
- Or pause for operator intervention

---

## Monitoring and Logging

### Real-Time Monitoring

View active executions in Shutdown Manager:
- Current step
- Progress percentage
- Elapsed time
- Status updates

### Execution Logs

Detailed logs include:
- Timestamp
- Log level (INFO/WARNING/ERROR/SUCCESS)
- Message
- Tag values
- Step details

### Audit Trail

All actions logged for compliance:
- Who initiated
- When started/completed
- Reason for shutdown
- Approvals given
- Any overrides used

---

## Best Practices

### 1. Sequence Design

✅ **Do:**
- Start with least critical equipment
- Include verification steps
- Add adequate wait times
- Use hold points for critical actions
- Document each step clearly

❌ **Don't:**
- Rush through steps
- Skip safety checks
- Remove hold points without review
- Use parallel execution for dependent actions

### 2. Interlocks

✅ **Do:**
- Set conservative limits
- Test all interlocks regularly
- Document bypass procedures
- Use multiple conditions for critical interlocks

❌ **Don't:**
- Allow permanent bypasses
- Set limits too close to normal operation
- Ignore interlock violations

### 3. Permissives

✅ **Do:**
- Verify all safety systems
- Check equipment readiness
- Include pressure/temperature checks
- Allow adequate tolerance

❌ **Don't:**
- Skip permissive checks
- Use overly tight tolerances
- Ignore failed permissives

### 4. Testing

✅ **Do:**
- Test sequences in simulation mode
- Verify all interlocks
- Practice emergency procedures
- Document test results

❌ **Don't:**
- Test on live production without safeguards
- Skip emergency shutdown testing
- Ignore failed tests

---

## Integration with SCADA

### Valve Control Integration

```php
// ShutdownManager uses ValveController
$valveController->controlValve($valveId, 'close', 0, [
    'initiated_by' => 'Shutdown System',
    'reason' => 'Automated shutdown sequence'
]);
```

### Tag Monitoring

```php
// Real-time tag value checking
SELECT current_value FROM scada_tags WHERE id = ?;

// Compare against setpoints
if ($currentValue > $alarmHigh) {
    triggerInterlock();
}
```

### Alarm Integration

```php
// Create alarm on shutdown
INSERT INTO scada_alarm_history
(tag_id, alarm_type, alarm_message, value)
VALUES (?, 'high', 'Emergency shutdown triggered', ?);
```

---

## API Reference

### ShutdownManager Methods

#### `initiateShutdown()`
```php
$result = $shutdownManager->initiateShutdown(
    $sequenceId,
    $initiatedBy,
    $reason,
    $isEmergency,
    $bypassInterlocks
);
```

#### `approveShutdown()`
```php
$result = $shutdownManager->approveShutdown(
    $executionId,
    $approvedBy
);
```

#### `continueExecution()`
```php
$result = $shutdownManager->continueExecution(
    $executionId,
    $confirmedBy
);
```

#### `abortExecution()`
```php
$result = $shutdownManager->abortExecution(
    $executionId,
    $abortedBy,
    $reason
);
```

#### `checkInterlocks()`
```php
$violations = $shutdownManager->checkInterlocks($siteId);
```

---

## Troubleshooting

### Problem: Sequence Won't Start

**Possible Causes:**
1. Interlocks violated
2. Permissives not satisfied
3. Approval required but not given
4. Equipment not responding

**Solutions:**
- Check interlock status
- Verify all permissives
- Ensure approvals processed
- Test equipment connectivity

### Problem: Step Timeout

**Possible Causes:**
1. Valve stuck
2. PLC not responding
3. Insufficient timeout setting
4. Network issues

**Solutions:**
- Manually verify equipment
- Check PLC communication
- Increase timeout if appropriate
- Review network connectivity

### Problem: Hold Point Not Clearing

**Possible Causes:**
1. Operator hasn't confirmed
2. Permissives failed
3. System awaiting approval

**Solutions:**
- Notify operator
- Check permissive conditions
- Verify approval workflow

---

## Compliance and Standards

### IEC 61511 Compliance

✅ Safety Instrumented System (SIS) separation
✅ Safety Integrity Level (SIL) consideration
✅ Proof testing procedures
✅ Bypass management
✅ Alarm management

### ISA-84 Compliance

✅ Safety lifecycle management
✅ Functional safety assessment
✅ Safety requirement specification
✅ Design verification
✅ Operation and maintenance

### API RP 14C Compliance

✅ Shutdown device requirements
✅ Emergency shutdown valve closure
✅ Alarm and shutdown level classification
✅ Cause and effect diagrams
✅ Testing procedures

---

## Security Considerations

### Access Control

- Role-based permissions
- Supervisor approval for critical actions
- Audit logging of all operations
- Bypass authorization tracking

### Data Integrity

- Timestamped logs (immutable)
- Sequence version control
- Configuration change tracking
- Backup and recovery procedures

---

## Maintenance

### Regular Tasks

**Daily:**
- Review execution logs
- Check active interlocks
- Verify system health

**Weekly:**
- Test emergency shutdown
- Review recent executions
- Check approval workflow

**Monthly:**
- Test all sequences
- Verify all interlocks
- Update documentation
- Review and update setpoints

**Annually:**
- Full system audit
- Compliance verification
- Safety review
- Training update

---

## Future Enhancements

🔲 SMS/Email notifications
🔲 Mobile app for approvals
🔲 Advanced analytics and reporting
🔲 Machine learning for optimization
🔲 Integration with maintenance system
🔲 Predictive shutdown triggers
🔲 3D visualization
🔲 VR training simulations

---

## Support

For issues or questions:
1. Check execution logs
2. Review interlock violations
3. Verify equipment status
4. Consult this documentation

---

## Summary

The Emergency Shutdown System provides:
- ✅ Automated, repeatable shutdown procedures
- ✅ Safety interlocks and permissives
- ✅ Compliance with industry standards
- ✅ Comprehensive logging and audit trail
- ✅ User-friendly operator interface
- ✅ Integration with existing SCADA infrastructure

**System Status:** Production Ready ✅

**Version:** 1.0.0

**Last Updated:** 2025-01-15
