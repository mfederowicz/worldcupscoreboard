package scoreboard

import (
	"errors"
	"regexp"
	"strings"
	"testing"
)

const uuidValue = "ffd44b6a-005e-4a56-9c1f-1f59c33ab2f7"

func TestUUIDIsCreatedFromString(t *testing.T) {
	id, err := UUIDFromString(uuidValue)
	if err != nil {
		t.Fatalf("UUIDFromString() error = %v", err)
	}

	if got := id.String(); got != uuidValue {
		t.Errorf("String() = %q, want %q", got, uuidValue)
	}
}

func TestUUIDIsNormalisedToLowerCase(t *testing.T) {
	id, err := UUIDFromString(strings.ToUpper(uuidValue))
	if err != nil {
		t.Fatalf("UUIDFromString() error = %v", err)
	}

	if got := id.String(); got != uuidValue {
		t.Errorf("String() = %q, want %q", got, uuidValue)
	}
}

func TestUUIDsWithSameValueAreEqual(t *testing.T) {
	first, _ := UUIDFromString(uuidValue)
	second, _ := UUIDFromString(strings.ToUpper(uuidValue))

	if first != second {
		t.Errorf("%v != %v, want equal", first, second)
	}
}

func TestInvalidUUIDIsRejected(t *testing.T) {
	tests := map[string]string{
		"empty":               "",
		"not a uuid":          "mexico-canada",
		"too short":           "ffd44b6a-005e-4a56-9c1f",
		"trailing characters": uuidValue + "-1",
		"without dashes":      "ffd44b6a005e4a569c1f1f59c33ab2f7",
	}

	for name, value := range tests {
		t.Run(name, func(t *testing.T) {
			if _, err := UUIDFromString(value); !errors.Is(err, ErrInvalidUUID) {
				t.Errorf("UUIDFromString(%q) error = %v, want ErrInvalidUUID", value, err)
			}
		})
	}
}

func TestNewUUIDIsVersion4(t *testing.T) {
	version4 := regexp.MustCompile(`^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$`)

	if id := NewUUID(); !version4.MatchString(id.String()) {
		t.Errorf("NewUUID() = %q, not a version 4 UUID", id)
	}
}

func TestEachNewUUIDIsDifferent(t *testing.T) {
	if first, second := NewUUID(), NewUUID(); first == second {
		t.Errorf("NewUUID() returned %q twice", first)
	}
}
