package scoreboard

import (
	"crypto/rand"
	"errors"
	"fmt"
	"regexp"
	"strings"
)

// ErrInvalidUUID is returned when a text is not a UUID.
var ErrInvalidUUID = errors.New("invalid UUID")

var uuidPattern = regexp.MustCompile(`^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$`)

// UUID is an identifier in the usual text form, always lower case.
// Two UUIDs with the same text are equal, so it can be compared with ==
// and used as a map key.
type UUID struct {
	value string
}

// NewUUID returns a new random UUID (version 4).
func NewUUID() UUID {
	var b [16]byte
	rand.Read(b[:]) // never returns an error, see crypto/rand

	b[6] = b[6]&0x0f | 0x40 // version 4
	b[8] = b[8]&0x3f | 0x80 // RFC 4122 variant

	return UUID{fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:16])}
}

// UUIDFromString returns the UUID written in value. Upper case letters are
// accepted and changed to lower case.
func UUIDFromString(value string) (UUID, error) {
	value = strings.ToLower(value)

	if !uuidPattern.MatchString(value) {
		return UUID{}, fmt.Errorf("%w: %q", ErrInvalidUUID, value)
	}

	return UUID{value}, nil
}

func (u UUID) String() string {
	return u.value
}
